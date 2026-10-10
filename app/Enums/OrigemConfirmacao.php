<?php

declare(strict_types=1);

namespace App\Enums;

/** Quem confirmou a presença no agendamento. */
enum OrigemConfirmacao: string
{
    case Paciente = 'paciente'; // respondeu SIM ao lembrete da véspera no WhatsApp
    case Equipe   = 'equipe';

    public function label(): string
    {
        return match ($this) {
            self::Paciente => 'pelo paciente no WhatsApp',
            self::Equipe   => 'pela equipe',
        };
    }
}
