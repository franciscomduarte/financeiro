<?php

declare(strict_types=1);

namespace App\Enums;

/** Tipos de pergunta que uma ficha de atendimento pode ter. */
enum TipoCampoFicha: string
{
    case TextoRico = 'texto_rico';
    case Texto     = 'texto';
    case Escolha   = 'escolha';
    case Multipla  = 'multipla';
    case SimNao    = 'sim_nao';
    case Numero    = 'numero';
    case Data      = 'data';
    case Titulo    = 'titulo';

    public function label(): string
    {
        return match ($this) {
            self::TextoRico => 'Texto com formatação',
            self::Texto     => 'Texto curto',
            self::Escolha   => 'Escolha uma opção',
            self::Multipla  => 'Várias opções',
            self::SimNao    => 'Sim ou não',
            self::Numero    => 'Número',
            self::Data      => 'Data',
            self::Titulo    => 'Título de seção',
        };
    }

    public function temOpcoes(): bool
    {
        return $this === self::Escolha || $this === self::Multipla;
    }
}
