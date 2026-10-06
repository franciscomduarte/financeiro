<?php

declare(strict_types=1);

namespace App\Enums;

/** Situação da notificação, da fila até a leitura. */
enum StatusNotificacao: string
{
    case Agendada  = 'agendada';
    case Enviando  = 'enviando';
    case Enviada   = 'enviada';
    case Entregue  = 'entregue';
    case Lida      = 'lida';
    case Falhou    = 'falhou';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Agendada  => 'Agendada',
            self::Enviando  => 'Enviando',
            self::Enviada   => 'Enviada',
            self::Entregue  => 'Entregue',
            self::Lida      => 'Lida',
            self::Falhou    => 'Falhou',
            self::Cancelada => 'Cancelada',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Agendada  => 'bg-sky-50 text-sky-700',
            self::Enviando  => 'bg-amber-50 text-amber-700',
            self::Enviada   => 'bg-violet-50 text-violet-700',
            self::Entregue  => 'bg-emerald-50 text-emerald-700',
            self::Lida      => 'bg-emerald-100 text-emerald-800',
            self::Falhou    => 'bg-red-50 text-red-700',
            self::Cancelada => 'bg-stone-100 text-stone-600',
        };
    }

    /** Ordem de avanço: o retorno de entrega só faz o status andar para frente. */
    public function etapa(): int
    {
        return match ($this) {
            self::Agendada, self::Enviando => 0,
            self::Enviada  => 1,
            self::Entregue => 2,
            self::Lida     => 3,
            self::Falhou, self::Cancelada => -1,
        };
    }

    /** @return array<int, self> */
    public static function doHistorico(): array
    {
        return [self::Enviando, self::Enviada, self::Entregue, self::Lida, self::Falhou, self::Cancelada];
    }
}
