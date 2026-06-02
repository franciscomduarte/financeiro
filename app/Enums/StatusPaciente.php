<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusPaciente: string
{
    case Ativo   = 'ativo';
    case Inativo = 'inativo';
}
