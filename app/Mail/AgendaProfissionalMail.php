<?php

declare(strict_types=1);

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Agenda do dia seguinte para o profissional se planejar. */
class AgendaProfissionalMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<int, array{inicio: string, fim: ?string, paciente: string, procedimento: string, confirmacao: string, observacoes: ?string, primeira_vez: bool}> $itens */
    public function __construct(
        public readonly string $profissional,
        public readonly CarbonImmutable $dia,
        public readonly array $itens,
        public readonly string $nomeClinica = '',
    ) {}

    public function envelope(): Envelope
    {
        $total = count($this->itens);

        return new Envelope(
            subject: "🗓️ Sua agenda de amanhã ({$this->dia->format('d/m')}) — {$total} " . ($total === 1 ? 'atendimento' : 'atendimentos'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.agenda-profissional');
    }
}
