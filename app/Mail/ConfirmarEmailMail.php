<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Link de confirmação de e-mail (autocadastro). */
class ConfirmarEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nome,
        public readonly string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirme seu e-mail — ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.plataforma.confirmar-email');
    }
}
