<?php

declare(strict_types=1);

namespace App\Enums;

enum RiscoContrato: string
{
    case Baixo = 'baixo';
    case Medio = 'medio';
    case Alto  = 'alto';
}
