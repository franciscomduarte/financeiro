<?php

declare(strict_types=1);

namespace App\Enums;

/** Modelos de texto do prontuário: termo de consentimento ou orientações ao paciente. */
enum TipoModeloProntuario: string
{
    case Termo      = 'termo';
    case Orientacao = 'orientacao';

    public function label(): string
    {
        return match ($this) {
            self::Termo      => 'Termo de consentimento',
            self::Orientacao => 'Orientações ao paciente',
        };
    }
}
