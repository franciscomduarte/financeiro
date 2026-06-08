<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusAgendamento: string
{
    case Agendado   = 'agendado';
    case Confirmado = 'confirmado';
    case Realizado  = 'realizado';
    case Cancelado  = 'cancelado';
    case Reagendado = 'reagendado';
    case Falta      = 'falta';

    public function label(): string
    {
        return match ($this) {
            self::Agendado   => 'Agendado',
            self::Confirmado => 'Confirmado',
            self::Realizado  => 'Realizado',
            self::Cancelado  => 'Cancelado',
            self::Reagendado => 'Reagendado',
            self::Falta      => 'Falta',
        };
    }

    public function isPendente(): bool
    {
        return in_array($this, [self::Agendado, self::Confirmado], true);
    }
}
