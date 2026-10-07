<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Enums\StatusFatura;
use App\Enums\StatusLancamentoFiscal;
use App\Enums\StatusTransacao;
use App\Models\ContaConsumoFatura;
use App\Models\ContratoPagamento;
use App\Models\ObrigacaoFiscalLancamento;
use App\Models\Transacao;

/**
 * Conta a pagar quitada (ou estornada) no financeiro reflete na origem: fatura de consumo paga,
 * guia de imposto paga, pagamento do contrato registrado. Chamado sempre que a situação muda.
 */
class SincronizarOrigemTituloAction
{
    public function execute(Transacao $t): void
    {
        $pago = $t->status === StatusTransacao::Pago;

        ContaConsumoFatura::query()->where('transacao_id', $t->id)->get()->each(function (ContaConsumoFatura $f) use ($t, $pago): void {
            if ($pago && $f->status !== StatusFatura::Paga) {
                $f->update(['status' => StatusFatura::Paga, 'data_pagamento' => $t->data_pagamento, 'valor' => $t->valor_bruto]);
            } elseif (! $pago && $f->status === StatusFatura::Paga) {
                $f->update(['status' => StatusFatura::Recebida, 'data_pagamento' => null]);
            }
        });

        ObrigacaoFiscalLancamento::query()->where('transacao_id', $t->id)->get()->each(function (ObrigacaoFiscalLancamento $g) use ($t, $pago): void {
            if ($pago && $g->status !== StatusLancamentoFiscal::Pago) {
                $g->update(['status' => StatusLancamentoFiscal::Pago, 'data_pagamento' => $t->data_pagamento]);
            } elseif (! $pago && $g->status === StatusLancamentoFiscal::Pago) {
                $g->update(['status' => StatusLancamentoFiscal::Pendente, 'data_pagamento' => null]);
            }
        });

        if ($t->contrato_id !== null) {
            $competencia = $t->data_competencia->format('Y-m');
            $pagamento = ContratoPagamento::query()->where('contrato_id', $t->contrato_id)->where('competencia', $competencia)->first();
            if ($pago && $pagamento === null) {
                ContratoPagamento::query()->create([
                    'contrato_id' => $t->contrato_id, 'competencia' => $competencia, 'valor' => $t->valor_bruto,
                    'data_pagamento' => $t->data_pagamento ?? today(), 'forma_pagamento' => $t->forma_pagamento?->value ?? 'boleto',
                    'transacao_id' => $t->id,
                ]);
            } elseif (! $pago && $pagamento?->transacao_id === $t->id) {
                $pagamento->delete();
            }
        }
    }
}
