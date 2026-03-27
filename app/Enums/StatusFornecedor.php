<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusFornecedor: string
{
    case Ativo     = 'ativo';
    case Suspenso  = 'suspenso';
    case Encerrado = 'encerrado';
}
