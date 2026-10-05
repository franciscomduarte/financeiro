<?php

declare(strict_types=1);

namespace App\Enums;

/** Momento da foto no prontuário, para comparar antes e depois. */
enum MomentoFoto: string
{
    case Antes          = 'antes';
    case Depois         = 'depois';
    case Acompanhamento = 'acompanhamento';

    public function label(): string
    {
        return match ($this) {
            self::Antes          => 'Antes',
            self::Depois         => 'Depois',
            self::Acompanhamento => 'Acompanhamento',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Antes          => 'bg-amber-50 text-amber-700',
            self::Depois         => 'bg-emerald-50 text-emerald-700',
            self::Acompanhamento => 'bg-sky-50 text-sky-700',
        };
    }
}
