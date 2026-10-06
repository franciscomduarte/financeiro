<?php

declare(strict_types=1);

namespace App\Support;

/** Telefones brasileiros: chave para comparar números e formato para exibir. */
final class Telefone
{
    /** Só os dígitos do número nacional (sem 55), ou null se não parecer telefone. */
    public static function nacional(?string $telefone): ?string
    {
        $d = preg_replace('/\D/', '', (string) $telefone) ?? '';
        if (strlen($d) >= 12 && str_starts_with($d, '55')) {
            $d = substr($d, 2);
        }

        return strlen($d) === 10 || strlen($d) === 11 ? $d : null;
    }

    /**
     * Chave de comparação: DDD + 8 últimos dígitos. O WhatsApp às vezes manda celulares sem o 9,
     * então "(61) 99999-1111" e "556199991111" casam.
     */
    public static function chave(?string $telefone): ?string
    {
        $d = self::nacional($telefone);

        return $d === null ? null : substr($d, 0, 2) . substr($d, -8);
    }

    /** "(61) 99999-1111". Celular sem o 9 (vindo do WhatsApp) ganha o 9 de volta. */
    public static function formatar(?string $telefone): ?string
    {
        $d = self::nacional($telefone);
        if ($d === null) {
            return null;
        }
        if (strlen($d) === 10 && in_array($d[2], ['6', '7', '8', '9'], true)) {
            $d = substr($d, 0, 2) . '9' . substr($d, 2);
        }

        return strlen($d) === 11
            ? sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7))
            : sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
    }
}
