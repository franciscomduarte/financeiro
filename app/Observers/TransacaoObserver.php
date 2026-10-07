<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Financeiro\BaixarTransacaoAction;
use App\Actions\Financeiro\GerarRecebiveisCartaoAction;
use App\Actions\Financeiro\SincronizarOrigemTituloAction;
use App\Enums\StatusTransacao;
use App\Models\ContaFinanceira;
use App\Models\PlanoConta;
use App\Models\Transacao;
use App\Models\TransacaoBaixa;

/**
 * Mantém as baixas coerentes quando o lançamento muda de situação pelos fluxos que só mexem no status
 * (marcar como pago, atendimento concluído, pagar contrato/fatura/guia, recorrência, API):
 *  - virou "pago": registra a baixa do que faltava na conta sugerida;
 *  - já pago e editado (valor, data, conta) com uma única baixa: ajusta essa baixa;
 *  - voltou para pendente ou foi cancelado: estorna as baixas.
 * A baixa feita pela tela de contas a pagar/receber grava sem eventos e não passa por aqui.
 */
class TransacaoObserver
{
    public function creating(Transacao $transacao): void
    {
        $transacao->data_vencimento ??= $transacao->data_competencia;
    }

    /** Categoria sempre ligada ao plano de contas (o nome continua gravado para telas e relatórios antigos). */
    public function saving(Transacao $transacao): void
    {
        $tipo = $transacao->tipo?->value ?? (string) $transacao->getAttribute('tipo');

        if ($transacao->categoria_id !== null && ($transacao->isDirty('categoria_id') || $transacao->isDirty('tipo'))) {
            $conta = PlanoConta::query()->select(['id', 'nome', 'tipo'])->find($transacao->categoria_id);
            if ($conta !== null && $conta->tipo->value === $tipo) {
                $transacao->categoria = $conta->nome;

                return;
            }
            $transacao->categoria_id = null;
        }

        if ($transacao->categoria_id === null || $transacao->isDirty(['categoria', 'tipo', 'subcategoria'])) {
            $transacao->categoria_id = PlanoConta::resolver($tipo, $transacao->categoria, $transacao->subcategoria)?->id;
        }
    }

    public function saved(Transacao $transacao): void
    {
        $mudouStatus = $transacao->wasRecentlyCreated || $transacao->wasChanged('status');

        if ($transacao->status === StatusTransacao::Pago) {
            if ($mudouStatus) {
                $this->baixarRestante($transacao);
            } elseif ($transacao->wasChanged(['valor_bruto', 'taxa_operacional', 'data_pagamento', 'conta_financeira_id', 'forma_pagamento'])) {
                $this->realinhar($transacao);
            }
        } elseif ($mudouStatus && in_array($transacao->status, [StatusTransacao::Pendente, StatusTransacao::Cancelado], true)
            && (float) $transacao->valor_pago > 0) {
            $transacao->baixas()->get()->each->delete();
            $transacao->valor_pago = 0;
            $transacao->saveQuietly();
        }

        if ($mudouStatus || $transacao->wasChanged('data_pagamento')) {
            app(SincronizarOrigemTituloAction::class)->execute($transacao);
        }
    }

    private function baixarRestante(Transacao $t): void
    {
        $restante = round((float) $t->valor_bruto - (float) $t->baixas()->sum('valor'), 2);
        if ($restante > 0.004) {
            $conta = $t->conta_financeira_id ?? ContaFinanceira::sugeridaPara($t->forma_pagamento)?->id;
            if ($conta === null) {
                return;
            }
            $taxa = BaixarTransacaoAction::taxaProporcional($t, $restante);
            $baixa = new TransacaoBaixa();
            $baixa->forceFill([
                'tenant_id'           => $t->tenant_id,
                'transacao_id'        => $t->id,
                'conta_financeira_id' => $conta,
                'tipo'                => $t->tipo,
                'data'                => $t->data_pagamento ?? today(),
                'valor'               => $restante,
                'taxa'                => $taxa,
                'valor_movimentado'   => round($restante - $taxa, 2),
                'forma_pagamento'     => $t->forma_pagamento,
                'user_id'             => auth()->id(),
            ])->save();
            app(GerarRecebiveisCartaoAction::class)->execute($baixa, $t->anteciparCartao);
        }
        $this->sincronizarValorPago($t);
    }

    /** Lançamento pago editado: com uma baixa só, ela acompanha valor, data e conta. */
    private function realinhar(Transacao $t): void
    {
        $baixas = $t->baixas()->get();
        if ($baixas->count() !== 1) {
            $this->baixarRestante($t);

            return;
        }

        /** @var TransacaoBaixa $baixa */
        $baixa = $baixas->first();
        $valor = round((float) $t->valor_bruto, 2);
        $taxa  = BaixarTransacaoAction::taxaProporcional($t, $valor);

        $baixa->fill([
            'valor'             => $valor,
            'taxa'              => $taxa,
            'valor_movimentado' => round($valor + (float) $baixa->juros + (float) $baixa->multa - (float) $baixa->desconto - $taxa, 2),
            'data'              => $t->data_pagamento ?? $baixa->data,
            'forma_pagamento'   => $t->forma_pagamento,
        ]);
        if ($t->wasChanged('conta_financeira_id') && $t->conta_financeira_id !== null) {
            $baixa->conta_financeira_id = $t->conta_financeira_id;
        }
        $baixa->save();
        app(GerarRecebiveisCartaoAction::class)->execute($baixa, $t->anteciparCartao ?? $baixa->antecipado);
        $this->sincronizarValorPago($t);
    }

    private function sincronizarValorPago(Transacao $t): void
    {
        $t->valor_pago = round((float) $t->baixas()->sum('valor'), 2);
        $t->saveQuietly();
    }
}
