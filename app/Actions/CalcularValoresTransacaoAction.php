<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FormaPagamento;
use App\Enums\TipoTransacao;
use App\Models\TaxaCartao;

class CalcularValoresTransacaoAction
{
    private const ALIQUOTA_IMPOSTO = 0.06;

    public function execute(
        float $valorBruto,
        FormaPagamento $formaPagamento,
        TipoTransacao $tipo,
    ): array {
        $taxaOperacional  = $this->calcularTaxaOperacional($valorBruto, $formaPagamento);
        $impostoEstimado  = $this->calcularImposto($valorBruto, $tipo);
        $valorLiquido     = $valorBruto - $taxaOperacional - $impostoEstimado;

        return [
            'taxa_operacional' => round($taxaOperacional, 2),
            'imposto_estimado' => round($impostoEstimado, 2),
            'valor_liquido'    => round($valorLiquido, 2),
        ];
    }

    private function calcularTaxaOperacional(float $valorBruto, FormaPagamento $formaPagamento): float
    {
        if ($formaPagamento->semTaxa()) {
            return 0.0;
        }

        $taxa = TaxaCartao::where('modalidade', $formaPagamento->value)
            ->where('ativo', true)
            ->select(['id', 'percentual'])
            ->first();

        if ($taxa === null) {
            return 0.0;
        }

        return $valorBruto * ((float) $taxa->percentual / 100);
    }

    private function calcularImposto(float $valorBruto, TipoTransacao $tipo): float
    {
        if ($tipo !== TipoTransacao::Entrada) {
            return 0.0;
        }

        return $valorBruto * self::ALIQUOTA_IMPOSTO;
    }
}
