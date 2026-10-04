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

    /** Classe Tailwind da bolinha de status no calendário. */
    public function corPonto(): string
    {
        return match ($this) {
            self::Agendado   => 'bg-violet-500',
            self::Confirmado => 'bg-emerald-500',
            self::Realizado  => 'bg-sky-500',
            self::Cancelado  => 'bg-red-500',
            self::Reagendado => 'bg-amber-500',
            self::Falta      => 'bg-stone-400',
        };
    }

    public function isPendente(): bool
    {
        return in_array($this, [self::Agendado, self::Confirmado], true);
    }
}
