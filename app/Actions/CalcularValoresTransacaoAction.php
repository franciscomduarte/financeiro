<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FormaPagamento;
use App\Enums\TipoTransacao;
use App\Models\TaxaCartao;
use App\Support\ClinicaAtual;

class CalcularValoresTransacaoAction
{
    /** Usada só se não houver clínica ativa (não deveria ocorrer). */
    private const ALIQUOTA_PADRAO = 0.06;

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function execute(
        float $valorBruto,
        FormaPagamento $formaPagamento,
        TipoTransacao $tipo,
    ): array {
        // Taxa de maquininha só incide quando a clínica recebe
        $taxaOperacional  = $tipo === TipoTransacao::Entrada
            ? $this->calcularTaxaOperacional($valorBruto, $formaPagamento)
            : 0.0;
        $impostoEstimado  = $this->calcularImposto($valorBruto, $tipo, $formaPagamento);
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

    private function calcularImposto(float $valorBruto, TipoTransacao $tipo, FormaPagamento $formaPagamento): float
    {
        if ($tipo !== TipoTransacao::Entrada) {
            return 0.0;
        }

        if ($formaPagamento->semImposto()) {
            return 0.0;
        }

        // Alíquota configurada por clínica (Configurações → Financeiro)
        $aliquota = $this->clinicaAtual->get()?->aliquotaImpostoFracao() ?? self::ALIQUOTA_PADRAO;

        return $valorBruto * $aliquota;
    }
}
