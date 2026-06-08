<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgendamentoReagendadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Agendamento $agendamento,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔄 Reagendamento confirmado — LC Estética',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agendamento.reagendado',
        );
    }
}
