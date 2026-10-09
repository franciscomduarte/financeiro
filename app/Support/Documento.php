<?php

declare(strict_types=1);

namespace App\Support;

/** CPF e CNPJ: só números e conferência dos dígitos verificadores. */
final class Documento
{
    public static function numeros(?string $documento): string
    {
        return (string) preg_replace('/\D/', '', (string) $documento);
    }

    /** CPF (11 números) ou CNPJ (14 números) com dígitos verificadores corretos. */
    public static function valido(?string $documento): bool
    {
        $numeros = self::numeros($documento);

        return match (strlen($numeros)) {
            11      => self::cpfValido($numeros),
            14      => self::cnpjValido($numeros),
            default => false,
        };
    }

    public static function cpfValido(?string $cpf): bool
    {
        $cpf = self::numeros($cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            if ((int) $cpf[$t] !== ((10 * $soma) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    public static function cnpjValido(?string $cnpj): bool
    {
        $cnpj = self::numeros($cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([12, 13] as $t) {
            $pesos = $t === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $soma  = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cnpj[$i] * $pesos[$i];
            }
            $resto = $soma % 11;
            if ((int) $cnpj[$t] !== ($resto < 2 ? 0 : 11 - $resto)) {
                return false;
            }
        }

        return true;
    }
}
