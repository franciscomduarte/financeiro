<?php

declare(strict_types=1);

namespace App\Enums;

/** Layout da NFS-e na Focus NFe: o da prefeitura ou o nacional (Sefin Nacional). */
enum PadraoNfse: string
{
    case Municipal = 'municipal';
    case Nacional  = 'nacional';

    /** Caminho da API v2 da Focus NFe. */
    public function endpoint(): string
    {
        return match ($this) {
            self::Municipal => '/v2/nfse',
            self::Nacional  => '/v2/nfsen',
        };
    }

    /** Evento do gatilho (webhook) da Focus NFe para este padrão. */
    public function evento(): string
    {
        return match ($this) {
            self::Municipal => 'nfse',
            self::Nacional  => 'nfsen',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Municipal => 'Padrão da prefeitura',
            self::Nacional  => 'Padrão nacional (NFS-e Nacional)',
        };
    }
}
