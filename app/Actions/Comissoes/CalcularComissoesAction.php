<?php

declare(strict_types=1);

namespace App\Actions\Comissoes;

use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\ComissaoFechamento;
use App\Models\PacoteSessao;
use App\Models\Profissional;
use App\Models\Transacao;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Comissão de cada profissional no mês: percentual sobre o que a clínica recebeu, já sem a taxa do cartão.
 * - Atendimentos avulsos: receitas pagas no mês ligadas a um atendimento do profissional.
 * - Pacotes: sessões feitas no mês, pelo valor de uma sessão (proporcional ao recebido), se o pacote já foi pago.
 */
class CalcularComissoesAction
{
    /**
     * @return Collection<int, array{profissional_id: string, nome: string, percentual: float, base_atendimentos: float,
     *         qtd_atendimentos: int, base_pacotes: float, qtd_sessoes: int, sessoes_a_receber: int, base: float, valor: float,
     *         fechamento: ?ComissaoFechamento}>
     */
    public function execute(string $competencia): Collection
    {
        [$inicio, $fim] = self::periodo($competencia);

        $atendimentos = Transacao::query()
            ->join('agendamentos', 'agendamentos.id', '=', 'transacoes.agendamento_id')
            ->where('transacoes.tipo', TipoTransacao::Entrada->value)
            ->where('transacoes.status', StatusTransacao::Pago->value)
            ->whereBetween('transacoes.data_pagamento', [$inicio, $fim])
            ->whereNotNull('agendamentos.profissional_id')
            ->groupBy('agendamentos.profissional_id')
            ->selectRaw('agendamentos.profissional_id, sum(transacoes.valor_bruto - transacoes.taxa_operacional) as base, count(*) as qtd')
            ->get()->keyBy('profissional_id');

        // Valor de uma sessão × parte recebida (sem a taxa) da venda do pacote
        $sessoes = PacoteSessao::query()
            ->join('pacotes', 'pacotes.id', '=', 'pacote_sessoes.pacote_id')
            ->join('transacoes', 'transacoes.id', '=', 'pacotes.transacao_id')
            ->whereBetween('pacote_sessoes.usada_em', [$inicio, $fim])
            ->whereNotNull('pacote_sessoes.profissional_id')
            ->groupBy('pacote_sessoes.profissional_id')
            ->selectRaw("pacote_sessoes.profissional_id,
                sum(case when transacoes.status = ? then pacotes.valor_total / pacotes.sessoes_total
                    * (transacoes.valor_bruto - transacoes.taxa_operacional) / nullif(transacoes.valor_bruto, 0) else 0 end) as base,
                sum(case when transacoes.status = ? then 1 else 0 end) as qtd,
                sum(case when transacoes.status = ? then 0 else 1 end) as a_receber",
                [StatusTransacao::Pago->value, StatusTransacao::Pago->value, StatusTransacao::Pago->value])
            ->get()->keyBy('profissional_id');

        $fechamentos = ComissaoFechamento::query()
            ->select(['id', 'profissional_id', 'competencia', 'base', 'percentual', 'valor', 'transacao_id', 'created_at'])
            ->where('competencia', $competencia)->get()->keyBy('profissional_id');

        $ids = $atendimentos->keys()->merge($sessoes->keys())->merge($fechamentos->keys())->unique();

        return Profissional::query()
            ->select(['id', 'nome', 'comissao_percentual', 'ativo'])
            ->where(fn ($q) => $q->where('ativo', true)->orWhereIn('id', $ids))
            ->orderBy('nome')
            ->limit(200)
            ->get()
            ->map(function (Profissional $p) use ($atendimentos, $sessoes, $fechamentos): array {
                $baseAtend  = round((float) ($atendimentos[$p->id]->base ?? 0), 2);
                $basePacote = round((float) ($sessoes[$p->id]->base ?? 0), 2);
                $base       = round($baseAtend + $basePacote, 2);
                $fechamento = $fechamentos[$p->id] ?? null;
                $percentual = $fechamento ? (float) $fechamento->percentual : (float) $p->comissao_percentual;

                return [
                    'profissional_id'   => $p->id,
                    'nome'              => $p->nome,
                    'percentual'        => $percentual,
                    'base_atendimentos' => $baseAtend,
                    'qtd_atendimentos'  => (int) ($atendimentos[$p->id]->qtd ?? 0),
                    'base_pacotes'      => $basePacote,
                    'qtd_sessoes'       => (int) ($sessoes[$p->id]->qtd ?? 0),
                    'sessoes_a_receber' => (int) ($sessoes[$p->id]->a_receber ?? 0),
                    'base'              => $base,
                    'valor'             => $fechamento ? (float) $fechamento->valor : round($base * $percentual / 100, 2),
                    'fechamento'        => $fechamento,
                ];
            });
    }

    /** @return array{0: string, 1: string} primeiro e último dia do mês AAAA-MM */
    public static function periodo(string $competencia): array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $competencia)) {
            throw new InvalidArgumentException('Mês inválido.');
        }

        $mes = CarbonImmutable::createFromFormat('Y-m-d', "{$competencia}-01");

        return [$mes->toDateString(), $mes->endOfMonth()->toDateString()];
    }
}
