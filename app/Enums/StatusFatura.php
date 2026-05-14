<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusFatura: string
{
    case Pendente  = 'pendente';
    case Recebida  = 'recebida';
    case Paga      = 'paga';
    case Vencida   = 'vencida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match($this) {
            self::Pendente  => 'Pendente',
            self::Recebida  => 'Recebida',
            self::Paga      => 'Paga',
            self::Vencida   => 'Vencida',
            self::Cancelada => 'Cancelada',
        };
    }

    public function podeSerPaga(): bool
    {
        return in_array($this, [self::Pendente, self::Recebida, self::Vencida], true);
    }
}
