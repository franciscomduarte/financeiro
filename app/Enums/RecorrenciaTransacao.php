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

    public function label(): string
    {
        return match ($this) {
            self::Unica      => 'Não repete',
            self::Mensal     => 'Todo mês',
            self::Trimestral => 'A cada 3 meses',
            self::Semestral  => 'A cada 6 meses',
            self::Anual      => 'Todo ano',
        };
    }

    /** Intervalo em meses entre um lançamento e o próximo (0 = não repete). */
    public function meses(): int
    {
        return match ($this) {
            self::Unica      => 0,
            self::Mensal     => 1,
            self::Trimestral => 3,
            self::Semestral  => 6,
            self::Anual      => 12,
        };
    }

    public function repete(): bool
    {
        return $this !== self::Unica;
    }
}
