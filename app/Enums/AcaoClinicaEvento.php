<?php

declare(strict_types=1);

namespace App\Enums;

/** Eventos do histórico de uma clínica (painel da plataforma). */
enum AcaoClinicaEvento: string
{
    case Cadastro        = 'cadastro';
    case Ativacao        = 'ativacao';
    case Bloqueio        = 'bloqueio';
    case Desbloqueio     = 'desbloqueio';
    case TesteEstendido  = 'teste_estendido';
    case SuporteEntrada  = 'suporte_entrada';
    case SuporteSaida    = 'suporte_saida';

    public function label(): string
    {
        return match ($this) {
            self::Cadastro       => 'Cadastro pelo "Assine já"',
            self::Ativacao       => 'Assinatura ativada',
            self::Bloqueio       => 'Clínica bloqueada',
            self::Desbloqueio    => 'Clínica desbloqueada',
            self::TesteEstendido => 'Teste estendido',
            self::SuporteEntrada => 'Entrou como suporte',
            self::SuporteSaida   => 'Saiu do modo suporte',
        };
    }
}
