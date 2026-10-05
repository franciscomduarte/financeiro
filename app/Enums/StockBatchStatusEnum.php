<?php

declare(strict_types=1);

namespace App\Enums;

enum StockBatchStatusEnum: string
{
    case Sealed    = 'sealed';
    case Open      = 'open';
    case Empty     = 'empty';
    case Expired   = 'expired';
    case Discarded = 'discarded';

    public function label(): string
    {
        return match($this) {
            self::Sealed    => 'Lacrado',
            self::Open      => 'Aberto',
            self::Empty     => 'Vazio',
            self::Expired   => 'Vencido',
            self::Discarded => 'Descartado',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Sealed    => 'text-blue-700 bg-blue-50 border-blue-200',
            self::Open      => 'text-emerald-700 bg-emerald-50 border-emerald-200',
            self::Empty     => 'text-stone-500 bg-stone-100 border-stone-200',
            self::Expired   => 'text-red-700 bg-red-50 border-red-200',
            self::Discarded => 'text-stone-500 bg-stone-100 border-stone-200',
        };
    }

    public function isUsable(): bool
    {
        return in_array($this, [self::Sealed, self::Open], true);
    }
}
