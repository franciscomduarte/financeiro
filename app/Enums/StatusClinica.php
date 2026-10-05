<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusClinica: string
{
    case Teste     = 'teste';
    case Ativa     = 'ativa';
    case Bloqueada = 'bloqueada';

    public function label(): string
    {
        return match ($this) {
            self::Teste     => 'Em teste',
            self::Ativa     => 'Ativa',
            self::Bloqueada => 'Bloqueada',
        };
    }
}
