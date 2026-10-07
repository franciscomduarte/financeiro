<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Actions\CreateTransacaoAction;
use App\Enums\StatusContrato;
use App\Enums\StatusFatura;
use App\Enums\StatusLancamentoFiscal;
use App\Models\ContaConsumoFatura;
use App\Models\Contrato;
use App\Models\ContratoPagamento;
use App\Models\ObrigacaoFiscalLancamento;
use App\Models\Transacao;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Contas fixas viram contas a pagar antes de serem pagas: fatura de consumo lançada, guia de imposto
 * e o mês de cada contrato ativo. Pagar pelo módulo de origem ou pelo Contas a pagar dá no mesmo
 * (a origem é sincronizada pelo SincronizarOrigemTituloAction). Roda ao lançar e todo dia.
 */
class TitulosContasFixasAction
{
    public function __construct(private readonly CreateTransacaoAction $criar) {}

    /** Gera o que estiver faltando para a clínica ativa (idempotente). */
    public function execute(): int
    {
        $criados = 0;

        ContaConsumoFatura::query()->whereNull('transacao_id')->whereNotNull('valor')
            ->whereIn('status', [StatusFatura::Recebida->value, StatusFatura::Vencida->value, StatusFatura::Pendente->value])
            ->limit(500)->get()
            ->each(function (ContaConsumoFatura $f) use (&$criados): void {
                $criados += $this->tentar(fn () => $this->fatura($f));
            });

        ObrigacaoFiscalLancamento::query()->whereNull('transacao_id')
            ->whereIn('status', [StatusLancamentoFiscal::Pendente->value, StatusLancamentoFiscal::Vencido->value])
            ->limit(500)->get()
            ->each(function (ObrigacaoFiscalLancamento $g) use (&$criados): void {
                $criados += $this->tentar(fn () => $this->guia($g));
            });

        $mes = CarbonImmutable::today()->startOfMonth();
        Contrato::query()->where('status', StatusContrato::Ativo->value)->whereNotNull('dia_vencimento')
            ->limit(500)->get()
            ->each(function (Contrato $c) use (&$criados, $mes): void {
                foreach ([$mes, $mes->addMonth()] as $competencia) {
                    $criados += $this->tentar(fn () => $this->contrato($c, $competencia));
                }
            });

        return $criados;
    }

    public function fatura(ContaConsumoFatura $fatura): ?Transacao
    {
        if ($fatura->transacao_id !== null || $fatura->valor === null || in_array($fatura->status, [StatusFatura::Paga, StatusFatura::Cancelada], true)) {
            return null;
        }

        return DB::transaction(function () use ($fatura): Transacao {
            $conta = $fatura->contaConsumo()->firstOrFail();
            $t = $this->criar->execute([
                'tipo' => 'saida', 'fase' => 'operacao', 'categoria' => $conta->tipo->categoria(), 'subcategoria' => $conta->tipo->label(),
                'descricao' => $conta->descricao . ' — ' . $fatura->competenciaFormatada(), 'fornecedor_id' => $conta->fornecedor_id,
                'valor_bruto' => (float) $fatura->valor, 'forma_pagamento' => 'boleto',
                'data_competencia' => $fatura->competencia . '-01', 'data_vencimento' => $fatura->data_vencimento?->toDateString() ?? $fatura->competencia . '-01',
                'status' => 'pendente', 'observacoes' => 'Fatura de consumo.',
            ]);
            $fatura->forceFill(['transacao_id' => $t->id])->save();

            return $t;
        });
    }

    public function guia(ObrigacaoFiscalLancamento $guia): ?Transacao
    {
        if ($guia->transacao_id !== null || ! in_array($guia->status, [StatusLancamentoFiscal::Pendente, StatusLancamentoFiscal::Vencido], true)) {
            return null;
        }

        return DB::transaction(function () use ($guia): Transacao {
            $obrigacao = $guia->obrigacaoFiscal()->firstOrFail();
            $t = $this->criar->execute([
                'tipo' => 'saida', 'fase' => 'operacao', 'categoria' => $obrigacao->tipo_tributo->categoria(), 'subcategoria' => $obrigacao->tipo_tributo->label(),
                'descricao' => $obrigacao->descricao . ' — ' . $guia->competenciaFormatada(), 'valor_bruto' => $guia->valorTotal(),
                'forma_pagamento' => 'boleto', 'data_competencia' => $guia->competencia . '-01',
                'data_vencimento' => $guia->data_vencimento?->toDateString() ?? $guia->competencia . '-01', 'status' => 'pendente',
            ]);
            $guia->forceFill(['transacao_id' => $t->id])->save();

            return $t;
        });
    }

    /** Conta a pagar do contrato no mês (se ainda não houver lançamento nem pagamento dessa competência). */
    public function contrato(Contrato $contrato, CarbonImmutable $mes): ?Transacao
    {
        $mes = $mes->startOfMonth();
        $competencia = $mes->format('Y-m');
        if ($contrato->data_inicio && $contrato->data_inicio->startOfMonth()->gt($mes)) {
            return null;
        }
        if ($contrato->data_fim && $contrato->data_fim->lt($mes)) {
            return null;
        }
        $jaTem = Transacao::query()->where('contrato_id', $contrato->id)->whereBetween('data_competencia', [$mes->toDateString(), $mes->endOfMonth()->toDateString()])->exists()
            || ContratoPagamento::query()->where('contrato_id', $contrato->id)->where('competencia', $competencia)->exists();
        if ($jaTem || (float) $contrato->getRawOriginal('valor_mensal') <= 0) {
            return null;
        }

        $fornecedor = $contrato->fornecedor()->value('nome_fantasia') ?? 'Fornecedor';

        return $this->criar->execute([
            'tipo' => 'saida', 'fase' => 'operacao', 'categoria' => 'servicos', 'subcategoria' => $fornecedor,
            'descricao' => "Contrato — {$fornecedor} — {$competencia}", 'fornecedor_id' => $contrato->fornecedor_id, 'contrato_id' => $contrato->id,
            'valor_bruto' => (float) $contrato->getRawOriginal('valor_mensal'), 'forma_pagamento' => 'boleto',
            'data_competencia' => $mes->toDateString(),
            'data_vencimento' => $mes->day(min((int) $contrato->dia_vencimento, $mes->daysInMonth))->toDateString(),
            'status' => 'pendente',
        ]);
    }

    private function tentar(callable $gerar): int
    {
        try {
            return $gerar() !== null ? 1 : 0;
        } catch (Throwable $e) {
            Log::error('[Financeiro] falha ao gerar conta a pagar de conta fixa', ['erro' => $e->getMessage()]);

            return 0;
        }
    }
}
