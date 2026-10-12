<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusDestinatarioCampanha: string
{
    case Pendente = 'pendente';
    case Enviado  = 'enviado';
    case Falhou   = 'falhou';
    case Ignorado = 'ignorado'; // sem celular válido ou anonimizado na hora do envio

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Na fila',
            self::Enviado  => 'Enviada',
            self::Falhou   => 'Falhou',
            self::Ignorado => 'Pulado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pendente => 'bg-stone-100 text-stone-600',
            self::Enviado  => 'bg-emerald-50 text-emerald-700',
            self::Falhou   => 'bg-red-50 text-red-700',
            self::Ignorado => 'bg-amber-50 text-amber-700',
        };
    }
}
