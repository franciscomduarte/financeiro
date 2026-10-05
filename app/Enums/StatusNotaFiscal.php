<?php

declare(strict_types=1);

namespace App\Enums;

/** Situação da NFS-e. */
enum StatusNotaFiscal: string
{
    case Processando = 'processando';
    case Autorizada  = 'autorizada';
    case Erro        = 'erro';
    case Cancelada   = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Processando => 'Processando',
            self::Autorizada  => 'Emitida',
            self::Erro        => 'Com erro',
            self::Cancelada   => 'Cancelada',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Processando => 'bg-amber-50 text-amber-700',
            self::Autorizada  => 'bg-emerald-50 text-emerald-700',
            self::Erro        => 'bg-red-50 text-red-700',
            self::Cancelada   => 'bg-stone-100 text-stone-600',
        };
    }

    /** Ocupa a receita: não deixa emitir outra nota para o mesmo lançamento. */
    public function ativa(): bool
    {
        return in_array($this, [self::Processando, self::Autorizada], true);
    }
}
