<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusTransacao: string
{
    case Pago = 'pago';
    case Pendente = 'pendente';
    case Parcial = 'parcial';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Pago      => 'Pago',
            self::Pendente  => 'Pendente',
            self::Parcial   => 'Pago em parte',
            self::Cancelado => 'Cancelado',
        };
    }

    /** Ainda há valor a pagar ou a receber. */
    public function emAberto(): bool
    {
        return $this === self::Pendente || $this === self::Parcial;
    }

    /** @return array<int, string> */
    public static function abertos(): array
    {
        return [self::Pendente->value, self::Parcial->value];
    }
}
