<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusLancamentoFiscal: string
{
    case Pendente  = 'pendente';
    case Pago      = 'pago';
    case Vencido   = 'vencido';
    case Parcelado = 'parcelado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match($this) {
            self::Pendente  => 'Pendente',
            self::Pago      => 'Pago',
            self::Vencido   => 'Vencido',
            self::Parcelado => 'Parcelado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function podePagar(): bool
    {
        return in_array($this, [self::Pendente, self::Vencido], true);
    }
}
