<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusDocumento: string
{
    case Vigente   = 'vigente';
    case Renovando = 'renovando';
    case Arquivado = 'arquivado';

    public function label(): string
    {
        return match ($this) {
            self::Vigente   => 'Vigente',
            self::Renovando => 'Em Renovação',
            self::Arquivado => 'Arquivado',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::Vigente   => 'green',
            self::Renovando => 'amber',
            self::Arquivado => 'slate',
        };
    }
}
