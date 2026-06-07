<?php

declare(strict_types=1);

namespace App\Enums;

enum StockUnitTypeEnum: string
{
    case UI       = 'UI';
    case Ml       = 'ml';
    case G        = 'g';
    case Mg       = 'mg';
    case Unidade  = 'unidade';
    case Ampola   = 'ampola';

    public function label(): string
    {
        return match($this) {
            self::UI      => 'UI (Unidades Internacionais)',
            self::Ml      => 'ml (mililitros)',
            self::G       => 'g (gramas)',
            self::Mg      => 'mg (miligramas)',
            self::Unidade => 'Unidade',
            self::Ampola  => 'Ampola',
        };
    }

    public function abbreviation(): string
    {
        return $this->value;
    }
}
