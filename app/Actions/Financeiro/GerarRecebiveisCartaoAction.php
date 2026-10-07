<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Enums\FormaPagamento;
use App\Enums\TipoContaFinanceira;
use App\Enums\TipoTransacao;
use App\Models\ContaFinanceira;
use App\Models\RecebivelCartao;
use App\Models\TransacaoBaixa;
use Carbon\CarbonImmutable;

/**
 * Venda no cartão recebida na conta Maquininha: agenda quando o dinheiro é liberado.
 *  - Parcelado (mês a mês): cada parcela no prazo de crédito × número da parcela (D+30, D+60...).
 *  - Antecipado: tudo no prazo de antecipação, descontando a taxa (% ao mês × meses antecipados).
 *  - Débito: uma liberação no prazo de débito.
 * Refeito quando a baixa muda (os ainda não liberados são recriados).
 */
class GerarRecebiveisCartaoAction
{
    public function execute(TransacaoBaixa $baixa, ?bool $antecipar = null): void
    {
        $forma = $baixa->forma_pagamento;
        $cartao = $forma === FormaPagamento::Debito || ($forma !== null && str_starts_with($forma->value, 'credito'));
        $conta  = ContaFinanceira::query()->find($baixa->conta_financeira_id);

        if ($baixa->tipo !== TipoTransacao::Entrada || ! $cartao || $conta?->tipo !== TipoContaFinanceira::Maquininha) {
            $baixa->recebiveis()->whereNull('liquidado_em')->delete();

            return;
        }
        if ($baixa->recebiveis()->whereNotNull('liquidado_em')->exists()) {
            return; // já caiu dinheiro na conta: não refaz
        }
        $baixa->recebiveis()->delete();

        $debito    = $forma === FormaPagamento::Debito;
        $antecipar = ! $debito && ($antecipar ?? $conta->antecipar_padrao);
        $base      = CarbonImmutable::parse($baixa->data);
        $total     = round((float) $baixa->valor_movimentado, 2);
        $n         = max(1, $forma->parcelas());

        $baixa->forceFill(['antecipado' => $debito ? null : $antecipar])->save();

        if ($total <= 0) {
            return;
        }

        $parcelas = [];
        $acumulado = 0.0;
        for ($i = 1; $i <= $n; $i++) {
            $valor = $i < $n ? round($total / $n, 2) : round($total - $acumulado, 2);
            $acumulado += $valor;
            $parcelas[$i] = $valor;
        }

        $comum = [
            'tenant_id' => $baixa->tenant_id, 'transacao_baixa_id' => $baixa->id, 'transacao_id' => $baixa->transacao_id,
            'conta_maquininha_id' => $conta->id,
        ];

        if ($debito) {
            $this->criar($comum + ['parcela' => 1, 'total_parcelas' => 1, 'antecipado' => false,
                'data_prevista' => $base->addDays($conta->prazo_debito_dias), 'valor' => $total, 'taxa_antecipacao' => 0, 'valor_liquido' => $total]);

            return;
        }

        if ($antecipar) {
            $taxa = 0.0;
            foreach ($parcelas as $i => $valor) {
                $meses = max(0, ($conta->prazo_credito_dias * $i - $conta->antecipacao_dias) / 30);
                $taxa += $valor * (float) $conta->taxa_antecipacao_mes / 100 * $meses;
            }
            $taxa = round($taxa, 2);
            $this->criar($comum + ['parcela' => 1, 'total_parcelas' => 1, 'antecipado' => true,
                'data_prevista' => $base->addDays($conta->antecipacao_dias), 'valor' => $total, 'taxa_antecipacao' => $taxa,
                'valor_liquido' => round($total - $taxa, 2)]);

            return;
        }

        foreach ($parcelas as $i => $valor) {
            $this->criar($comum + ['parcela' => $i, 'total_parcelas' => $n, 'antecipado' => false,
                'data_prevista' => $base->addDays($conta->prazo_credito_dias * $i), 'valor' => $valor, 'taxa_antecipacao' => 0, 'valor_liquido' => $valor]);
        }
    }

    private function criar(array $dados): void
    {
        (new RecebivelCartao())->forceFill($dados)->save();
    }
}
