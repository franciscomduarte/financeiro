<?php

declare(strict_types=1);

namespace App\Enums;

enum VisaoAgenda: string
{
    case Dia    = 'dia';
    case Semana = 'semana';
    case Mes    = 'mes';
    case Lista  = 'lista';

    public function label(): string
    {
        return match ($this) {
            self::Dia    => 'Dia',
            self::Semana => 'Semana',
            self::Mes    => 'Mês',
            self::Lista  => 'Lista',
        };
    }

    public function isCalendario(): bool
    {
        return $this !== self::Lista;
    }
}
