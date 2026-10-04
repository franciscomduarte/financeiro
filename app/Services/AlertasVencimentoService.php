<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AlertaVencimento;
use App\Enums\StatusContrato;
use App\Enums\StatusDocumento;
use App\Enums\StatusFatura;
use App\Enums\StatusLancamentoFiscal;
use App\Enums\StatusTransacao;
use App\Enums\TipoAlertaVencimento;
use App\Enums\TipoTransacao;
use App\Models\ContaConsumoFatura;
use App\Models\Contrato;
use App\Models\Documento;
use App\Models\ObrigacaoFiscalLancamento;
use App\Models\Transacao;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Reúne o que vence (ou já venceu) nos módulos financeiros e administrativos.
 * Fonte única para o resumo diário (e-mail/WhatsApp) e para o aviso no Dashboard.
 */
class AlertasVencimentoService
{
    /** Contas (despesas, faturas, guias): avisar a partir de N dias antes. */
    public const DIAS_ANTES_CONTAS = 3;

    /** Contratos: avisar a partir de N dias antes do fim (requisitos, seção 9). */
    public const DIAS_ANTES_CONTRATOS = 60;

    /** Teto por fonte, para nunca carregar sem limite. */
    private const LIMITE_POR_FONTE = 100;

    /**
     * @return array{vencidos: Collection<int, AlertaVencimento>, a_vencer: Collection<int, AlertaVencimento>}
     */
    public function levantar(?CarbonImmutable $hoje = null): array
    {
        $hoje ??= CarbonImmutable::today();

        $alertas = collect()
            ->concat($this->despesas($hoje))
            ->concat($this->faturas($hoje))
            ->concat($this->guias($hoje))
            ->concat($this->contratos($hoje))
            ->concat($this->pagamentosContrato($hoje))
            ->concat($this->documentos($hoje))
            ->sortBy(fn (AlertaVencimento $a) => $a->data->getTimestamp())
            ->values();

        [$vencidos, $aVencer] = $alertas->partition(fn (AlertaVencimento $a) => $a->data->lt($hoje));

        return ['vencidos' => $vencidos->values(), 'a_vencer' => $aVencer->values()];
    }

    /** @return Collection<int, AlertaVencimento> */
    private function despesas(CarbonImmutable $hoje): Collection
    {
        return Transacao::query()
            ->select(['id', 'descricao', 'valor_bruto', 'data_competencia'])
            ->where('tipo', TipoTransacao::Saida->value)
            ->where('status', StatusTransacao::Pendente->value)
            ->where('data_competencia', '<=', $hoje->addDays(self::DIAS_ANTES_CONTAS)->toDateString())
            ->orderBy('data_competencia')
            ->limit(self::LIMITE_POR_FONTE)
            ->get()
            ->map(fn (Transacao $t) => new AlertaVencimento(
                TipoAlertaVencimento::Despesa,
                $t->descricao,
                $t->data_competencia->toImmutable(),
                (float) $t->valor_bruto,
            ));
    }

    /** @return Collection<int, AlertaVencimento> */
    private function faturas(CarbonImmutable $hoje): Collection
    {
        return ContaConsumoFatura::query()
            ->with('contaConsumo:id,descricao,tipo')
            ->select(['id', 'conta_consumo_id', 'competencia', 'data_vencimento', 'valor'])
            ->whereIn('status', [StatusFatura::Pendente->value, StatusFatura::Recebida->value, StatusFatura::Vencida->value])
            ->whereNotNull('data_vencimento')
            ->where('data_vencimento', '<=', $hoje->addDays(self::DIAS_ANTES_CONTAS)->toDateString())
            ->orderBy('data_vencimento')
            ->limit(self::LIMITE_POR_FONTE)
            ->get()
            ->map(fn (ContaConsumoFatura $f) => new AlertaVencimento(
                TipoAlertaVencimento::Fatura,
                trim(($f->contaConsumo?->descricao ?: $f->contaConsumo?->tipo?->label() ?? 'Conta') . ' — ' . $f->competenciaFormatada()),
                $f->data_vencimento->toImmutable(),
                $f->valor !== null ? (float) $f->valor : null,
            ));
    }

    /** @return Collection<int, AlertaVencimento> */
    private function guias(CarbonImmutable $hoje): Collection
    {
        return ObrigacaoFiscalLancamento::query()
            ->with('obrigacaoFiscal:id,descricao')
            ->select(['id', 'obrigacao_fiscal_id', 'competencia', 'data_vencimento', 'valor_principal', 'valor_multa', 'valor_juros'])
            ->whereIn('status', [StatusLancamentoFiscal::Pendente->value, StatusLancamentoFiscal::Vencido->value])
            ->where('data_vencimento', '<=', $hoje->addDays(self::DIAS_ANTES_CONTAS)->toDateString())
            ->orderBy('data_vencimento')
            ->limit(self::LIMITE_POR_FONTE)
            ->get()
            ->map(fn (ObrigacaoFiscalLancamento $g) => new AlertaVencimento(
                TipoAlertaVencimento::Guia,
                trim(($g->obrigacaoFiscal?->descricao ?? 'Guia') . ' — ' . $g->competenciaFormatada()),
                $g->data_vencimento->toImmutable(),
                $g->valorTotal(),
            ));
    }

    /** @return Collection<int, AlertaVencimento> */
    private function contratos(CarbonImmutable $hoje): Collection
    {
        return Contrato::query()
            ->with('fornecedor:id,nome_fantasia,razao_social')
            ->select(['id', 'fornecedor_id', 'data_fim', 'valor_mensal'])
            ->where('status', StatusContrato::Ativo->value)
            ->whereNotNull('data_fim')
            ->where('data_fim', '<=', $hoje->addDays(self::DIAS_ANTES_CONTRATOS)->toDateString())
            ->orderBy('data_fim')
            ->limit(self::LIMITE_POR_FONTE)
            ->get()
            ->map(fn (Contrato $c) => new AlertaVencimento(
                TipoAlertaVencimento::Contrato,
                'Contrato ' . ($c->fornecedor?->nome_fantasia ?: $c->fornecedor?->razao_social ?: 'sem fornecedor') . ' — fim do contrato',
                $c->data_fim->toImmutable(),
            ));
    }

    /**
     * Mensalidade de contratos ativos (ex.: aluguel) do mês atual e do próximo que ainda não
     * foi registrada como paga e vence em até DIAS_ANTES_CONTAS dias (ou já venceu neste mês).
     *
     * @return Collection<int, AlertaVencimento>
     */
    private function pagamentosContrato(CarbonImmutable $hoje): Collection
    {
        $competencias = [$hoje->format('Y-m'), $hoje->addMonthNoOverflow()->format('Y-m')];
        $limite       = $hoje->addDays(self::DIAS_ANTES_CONTAS);

        return Contrato::query()
            ->with([
                'fornecedor:id,nome_fantasia,razao_social',
                'pagamentos' => fn ($q) => $q->select(['id', 'contrato_id', 'competencia'])->whereIn('competencia', $competencias),
            ])
            ->select(['id', 'fornecedor_id', 'dia_vencimento', 'valor_mensal', 'data_inicio', 'data_fim'])
            ->where('status', StatusContrato::Ativo->value)
            ->whereNotNull('dia_vencimento')
            ->limit(self::LIMITE_POR_FONTE)
            ->get()
            ->flatMap(function (Contrato $c) use ($competencias, $hoje, $limite): array {
                $pagas  = $c->pagamentos->pluck('competencia')->all();
                $nome   = $c->fornecedor?->nome_fantasia ?: $c->fornecedor?->razao_social ?: 'contrato';
                $itens  = [];

                foreach ($competencias as $competencia) {
                    $mes        = CarbonImmutable::createFromFormat('!Y-m', $competencia);
                    $vencimento = $mes->day(min($c->dia_vencimento, $mes->daysInMonth));

                    $foraDoContrato = ($c->data_inicio && $vencimento->lt($c->data_inicio->startOfDay()))
                        || ($c->data_fim && $vencimento->gt($c->data_fim->startOfDay()));

                    if (in_array($competencia, $pagas, true) || $foraDoContrato || $vencimento->gt($limite)) {
                        continue;
                    }

                    $itens[] = new AlertaVencimento(
                        TipoAlertaVencimento::PagamentoContrato,
                        "Mensalidade {$nome} — " . $mes->format('m/Y'),
                        $vencimento,
                        $c->valor_mensal !== null ? (float) $c->valor_mensal : null,
                    );
                }

                return $itens;
            });
    }

    /** @return Collection<int, AlertaVencimento> */
    private function documentos(CarbonImmutable $hoje): Collection
    {
        // O prazo de alerta varia por documento/categoria; filtra no banco pelo maior prazo plausível
        // e refina com a regra do próprio model.
        return Documento::query()
            ->with('categoria:id,alerta_dias_antes')
            ->select(['id', 'titulo', 'categoria_id', 'data_validade', 'alerta_dias_antes', 'status'])
            ->where('status', '!=', StatusDocumento::Arquivado->value)
            ->whereNotNull('data_validade')
            ->where('data_validade', '<=', $hoje->addYear()->toDateString())
            ->orderBy('data_validade')
            ->limit(self::LIMITE_POR_FONTE)
            ->get()
            ->filter(fn (Documento $d) => $d->data_validade->toImmutable()->lt($hoje)
                || $hoje->diffInDays($d->data_validade->toImmutable()) <= $d->alertaDias())
            ->map(fn (Documento $d) => new AlertaVencimento(
                TipoAlertaVencimento::Documento,
                $d->titulo,
                $d->data_validade->toImmutable(),
            ))
            ->values();
    }
}
