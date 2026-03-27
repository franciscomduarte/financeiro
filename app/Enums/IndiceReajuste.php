<?php

declare(strict_types=1);

namespace App\Enums;

enum IndiceReajuste: string
{
    case Igpm  = 'igpm';
    case Ipca  = 'ipca';
    case Inpc  = 'inpc';
    case Fixo  = 'fixo';
    case Livre = 'livre';
}
