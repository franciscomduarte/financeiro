<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoTransacao: string
{
    case Entrada = 'entrada';
    case Saida = 'saida';
}
