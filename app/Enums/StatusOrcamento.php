<?php

declare(strict_types=1);

namespace App\Enums;

/** Situação do orçamento. "Vencido" é derivado da validade (ver Orcamento::situacao()). */
enum StatusOrcamento: string
{
    case Aberto   = 'aberto';
    case Aprovado = 'aprovado';
    case Recusado = 'recusado';

    public function label(): string
    {
        return match ($this) {
            self::Aberto   => 'Aberto',
            self::Aprovado => 'Aprovado',
            self::Recusado => 'Recusado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Aberto   => 'bg-amber-50 text-amber-700',
            self::Aprovado => 'bg-emerald-50 text-emerald-700',
            self::Recusado => 'bg-stone-100 text-stone-600',
        };
    }
}
