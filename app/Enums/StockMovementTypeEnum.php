<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementTypeEnum: string
{
    case Purchase       = 'purchase';
    case ProcedureUse   = 'procedure_use';
    case ManualExit     = 'manual_exit';
    case DiscardExpired = 'discard_expired';
    case Adjustment     = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::Purchase       => 'Entrada de Compra',
            self::ProcedureUse   => 'Uso em Procedimento',
            self::ManualExit     => 'Saída Manual',
            self::DiscardExpired => 'Descarte (Vencido)',
            self::Adjustment     => 'Ajuste de Estoque',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Purchase       => 'text-emerald-700 bg-emerald-50 border-emerald-200',
            self::ProcedureUse   => 'text-blue-700 bg-blue-50 border-blue-200',
            self::ManualExit     => 'text-amber-700 bg-amber-50 border-amber-200',
            self::DiscardExpired => 'text-red-700 bg-red-50 border-red-200',
            self::Adjustment     => 'text-violet-700 bg-violet-50 border-violet-200',
        };
    }

    public function isEntry(): bool
    {
        return $this === self::Purchase;
    }
}
