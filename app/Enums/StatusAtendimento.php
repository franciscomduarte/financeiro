<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusAtendimento: string
{
    case EmAndamento = 'em_andamento';
    case Finalizado  = 'finalizado';

    public function label(): string
    {
        return match ($this) {
            self::EmAndamento => 'Em andamento',
            self::Finalizado  => 'Finalizado',
        };
    }
}
