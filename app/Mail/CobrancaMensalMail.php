<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Paciente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CobrancaMensalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Paciente $paciente,
        public readonly array $cobranca,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💰 Cobrança mensal — ' . now()->translatedFormat('F/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.cobranca-mensal');
    }
}
