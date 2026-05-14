<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoTributo: string
{
    case DasSimples = 'das_simples';
    case Darf      = 'darf';
    case GpsInss   = 'gps_inss';
    case Fgts      = 'fgts';
    case Iss       = 'iss';
    case Irrf      = 'irrf';
    case Outro     = 'outro';

    public function label(): string
    {
        return match($this) {
            self::DasSimples => 'DAS — Simples Nacional',
            self::Darf       => 'DARF',
            self::GpsInss    => 'GPS — INSS',
            self::Fgts       => 'FGTS',
            self::Iss        => 'ISS',
            self::Irrf       => 'IRRF',
            self::Outro      => 'Outro',
        };
    }

    public function categoria(): string
    {
        return match($this) {
            self::DasSimples => 'Simples Nacional',
            self::Darf       => 'Impostos Federais',
            self::GpsInss    => 'Encargos Sociais',
            self::Fgts       => 'Encargos Sociais',
            self::Iss        => 'Impostos Municipais',
            self::Irrf       => 'Impostos Federais',
            self::Outro      => 'Tributos',
        };
    }

    public function temCodigoReceita(): bool
    {
        return $this === self::Darf || $this === self::Irrf;
    }

    public function cor(): string
    {
        return match($this) {
            self::DasSimples => 'blue',
            self::Darf       => 'red',
            self::GpsInss    => 'emerald',
            self::Fgts       => 'teal',
            self::Iss        => 'violet',
            self::Irrf       => 'orange',
            self::Outro      => 'slate',
        };
    }
}
