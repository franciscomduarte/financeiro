<?php

declare(strict_types=1);

namespace App\Enums;

enum FaseTransacao: string
{
    case Implantacao = 'implantacao';
    case Operacao = 'operacao';

    public function label(): string
    {
        return match ($this) {
            self::Implantacao => 'Implantação',
            self::Operacao    => 'Operação',
        };
    }
}
