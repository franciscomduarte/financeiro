<?php

declare(strict_types=1);

namespace App\Enums;

/** Situação do pacote de sessões vendido ao paciente. */
enum StatusPacote: string
{
    case Ativo     = 'ativo';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Ativo     => 'Ativo',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Ativo     => 'bg-emerald-50 text-emerald-700',
            self::Concluido => 'bg-sky-50 text-sky-700',
            self::Cancelado => 'bg-stone-100 text-stone-600',
        };
    }
}
