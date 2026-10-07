<?php

declare(strict_types=1);

namespace App\Enums;

/** Etapas fixas do funil de leads. */
enum EtapaLead: string
{
    case Novo              = 'novo';
    case EmContato         = 'em_contato';
    case AvaliacaoAgendada = 'avaliacao_agendada';
    case OrcamentoEnviado  = 'orcamento_enviado';
    case Fechado           = 'fechado';
    case Perdido           = 'perdido';

    public function label(): string
    {
        return match ($this) {
            self::Novo              => 'Novo',
            self::EmContato         => 'Em contato',
            self::AvaliacaoAgendada => 'Avaliação agendada',
            self::OrcamentoEnviado  => 'Orçamento enviado',
            self::Fechado           => 'Fechado',
            self::Perdido           => 'Perdido',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Novo              => 'bg-sky-50 text-sky-700',
            self::EmContato         => 'bg-amber-50 text-amber-700',
            self::AvaliacaoAgendada => 'bg-violet-50 text-violet-700',
            self::OrcamentoEnviado  => 'bg-rose-50 text-rose-700',
            self::Fechado           => 'bg-emerald-50 text-emerald-700',
            self::Perdido           => 'bg-stone-100 text-stone-600',
        };
    }

    /** Ainda em negociação (aparece nos alertas de contato). */
    public function aberta(): bool
    {
        return $this !== self::Fechado && $this !== self::Perdido;
    }

    /** @return array<int, self> */
    public static function abertas(): array
    {
        return array_values(array_filter(self::cases(), fn (self $e) => $e->aberta()));
    }

    /** Motivos de perda sugeridos. @return array<int, string> */
    public static function motivosPerda(): array
    {
        return ['Achou caro', 'Parou de responder', 'Fechou com outra clínica', 'Só estava pesquisando', 'Não pode no momento', 'Outro'];
    }
}
