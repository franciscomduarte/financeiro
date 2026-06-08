<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoNotificacaoAgendamento: string
{
    case Confirmacao   = 'confirmacao';
    case Cancelamento  = 'cancelamento';
    case Reagendamento = 'reagendamento';
    case Lembrete1Dia  = 'lembrete_1dia';
    case Lembrete2Horas = 'lembrete_2horas';

    public function subject(): string
    {
        return match ($this) {
            self::Confirmacao   => '📅 Agendamento confirmado',
            self::Cancelamento  => '❌ Agendamento cancelado',
            self::Reagendamento => '🔄 Reagendamento confirmado',
            self::Lembrete1Dia  => '⏰ Lembrete: consulta amanhã',
            self::Lembrete2Horas => '⏰ Lembrete: consulta em 2 horas',
        };
    }
}
