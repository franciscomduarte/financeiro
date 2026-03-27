<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusTransacao: string
{
    case Pago = 'pago';
    case Pendente = 'pendente';
    case Cancelado = 'cancelado';
}
