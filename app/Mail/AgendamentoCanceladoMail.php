<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\ClinicaAtual;
use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgendamentoCanceladoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Agendamento $agendamento,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Agendamento cancelado — ' . app(ClinicaAtual::class)->nome(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agendamento.cancelado',
        );
    }
}
