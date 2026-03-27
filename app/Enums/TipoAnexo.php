<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoAnexo: string
{
    case Boleto = 'boleto';
    case Comprovante = 'comprovante';
}
