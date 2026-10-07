<?php

declare(strict_types=1);

namespace App\Support;

/** Valores em reais digitados no formato brasileiro ("1.234,56", "1234,56", "1234.56"). */
final class Dinheiro
{
    public static function numero(?string $valor): float
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return 0.0;
        }
        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor); // "1.234,56" → "1234.56"
        }

        return round((float) $valor, 2);
    }

    /** Para preencher um campo de valor: "1234,56". */
    public static function paraCampo(float $valor): string
    {
        return number_format($valor, 2, ',', '');
    }

    /** Para exibir: "R$ 1.234,56". */
    public static function formatar(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}
