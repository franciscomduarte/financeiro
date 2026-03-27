<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusContrato: string
{
    case Ativo         = 'ativo';
    case EmNegociacao  = 'em_negociacao';
    case Encerrado     = 'encerrado';
    case Suspenso      = 'suspenso';
}
