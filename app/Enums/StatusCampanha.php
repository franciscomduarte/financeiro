<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusCampanha: string
{
    case Rascunho  = 'rascunho';
    case Enviando  = 'enviando';
    case Pausada   = 'pausada';
    case Concluida = 'concluida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho  => 'Rascunho',
            self::Enviando  => 'Enviando',
            self::Pausada   => 'Pausada',
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Rascunho  => 'bg-stone-100 text-stone-600',
            self::Enviando  => 'bg-sky-50 text-sky-700',
            self::Pausada   => 'bg-amber-50 text-amber-700',
            self::Concluida => 'bg-emerald-50 text-emerald-700',
            self::Cancelada => 'bg-red-50 text-red-700',
        };
    }

    public function editavel(): bool
    {
        return $this === self::Rascunho;
    }
}
