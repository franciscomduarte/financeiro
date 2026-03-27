<?php

declare(strict_types=1);

namespace App\Enums;

enum RecorrenciaTransacao: string
{
    case Unica = 'unica';
    case Mensal = 'mensal';
    case Trimestral = 'trimestral';
    case Semestral = 'semestral';
    case Anual = 'anual';
}
