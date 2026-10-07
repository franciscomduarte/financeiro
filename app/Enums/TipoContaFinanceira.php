<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoContaFinanceira: string
{
    case Caixa      = 'caixa';
    case Banco      = 'banco';
    case Maquininha = 'maquininha';
    case Outra      = 'outra';

    public function label(): string
    {
        return match ($this) {
            self::Caixa      => 'Caixa (dinheiro)',
            self::Banco      => 'Conta bancária',
            self::Maquininha => 'Maquininha de cartão',
            self::Outra      => 'Outra',
        };
    }
}
