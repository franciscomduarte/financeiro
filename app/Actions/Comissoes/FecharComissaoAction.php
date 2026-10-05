<?php

declare(strict_types=1);

namespace App\Actions\Comissoes;

use App\Actions\CreateTransacaoAction;
use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\ComissaoFechamento;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Fecha a comissão do profissional no mês: grava o valor e lança a despesa a pagar em Lançamentos. */
class FecharComissaoAction
{
    public function __construct(
        private readonly CalcularComissoesAction $calcular,
        private readonly CreateTransacaoAction $createTransacao,
    ) {}

    public function execute(string $profissionalId, string $competencia): ComissaoFechamento
    {
        [, $fim] = CalcularComissoesAction::periodo($competencia);
        if ($competencia >= now()->format('Y-m')) {
            throw new RuntimeException('Feche a comissão depois que o mês terminar.');
        }

        return DB::transaction(function () use ($profissionalId, $competencia, $fim): ComissaoFechamento {
            if (ComissaoFechamento::query()->where('profissional_id', $profissionalId)->where('competencia', $competencia)->lockForUpdate()->exists()) {
                throw new RuntimeException('A comissão deste mês já foi fechada.');
            }

            $linha = $this->calcular->execute($competencia)->firstWhere('profissional_id', $profissionalId)
                ?? throw new RuntimeException('Profissional não encontrado.');

            if ($linha['valor'] <= 0) {
                throw new RuntimeException('Não há comissão a pagar neste mês. Confira o percentual do profissional.');
            }

            $mes     = CarbonImmutable::createFromFormat('Y-m-d', "{$competencia}-01")->format('m/Y');
            $despesa = $this->createTransacao->execute([
                'tipo'             => TipoTransacao::Saida->value,
                'fase'             => FaseTransacao::Operacao->value,
                'categoria'        => 'Pessoal',
                'subcategoria'     => 'Comissões',
                'descricao'        => mb_substr("Comissão {$mes} — {$linha['nome']}", 0, 255),
                'cliente'          => $linha['nome'],
                'valor_bruto'      => $linha['valor'],
                'forma_pagamento'  => FormaPagamento::Pix->value,
                'data_competencia' => $fim,
                'status'           => StatusTransacao::Pendente->value,
                'observacoes'      => sprintf('%s%% sobre R$ %s recebidos (%d atendimentos e %d sessões de pacote).',
                    rtrim(rtrim(number_format($linha['percentual'], 2, ',', ''), '0'), ','),
                    number_format($linha['base'], 2, ',', '.'), $linha['qtd_atendimentos'], $linha['qtd_sessoes']),
            ]);

            $fechamento = ComissaoFechamento::create([
                'profissional_id' => $profissionalId,
                'competencia'     => $competencia,
                'base'            => $linha['base'],
                'percentual'      => $linha['percentual'],
                'valor'           => $linha['valor'],
                'transacao_id'    => $despesa->id,
                'user_id'         => auth()->id(),
            ]);

            Log::info('[Comissoes] comissão fechada', [
                'profissional_id' => $profissionalId, 'competencia' => $competencia, 'valor' => $linha['valor'], 'user_id' => auth()->id(),
            ]);

            return $fechamento;
        });
    }
}
