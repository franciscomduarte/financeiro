<?php

declare(strict_types=1);

namespace App\Enums;

enum PeriodicidadeFiscal: string
{
    case Mensal      = 'mensal';
    case Trimestral  = 'trimestral';
    case Semestral   = 'semestral';
    case Anual       = 'anual';
    case Eventual    = 'eventual';

    public function label(): string
    {
        return match($this) {
            self::Mensal     => 'Mensal',
            self::Trimestral => 'Trimestral',
            self::Semestral  => 'Semestral',
            self::Anual      => 'Anual',
            self::Eventual   => 'Eventual',
        };
    }
}
