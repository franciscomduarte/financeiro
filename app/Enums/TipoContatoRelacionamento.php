<?php

declare(strict_types=1);

namespace App\Enums;

/** Tipos de contato de relacionamento feitos pela recepção. */
enum TipoContatoRelacionamento: string
{
    case Retorno     = 'retorno';
    case Aniversario = 'aniversario';
    case Sumido      = 'sumido';

    public function label(): string
    {
        return match ($this) {
            self::Retorno     => 'Lembrete de retorno',
            self::Aniversario => 'Parabéns',
            self::Sumido      => 'Convite para voltar',
        };
    }
}
