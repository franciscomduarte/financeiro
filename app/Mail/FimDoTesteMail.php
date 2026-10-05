<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Clinica;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso aos administradores: o teste grátis termina em $diasParaFim dias (0 = hoje é o último dia). */
class FimDoTesteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Clinica $clinica,
        public readonly int $diasParaFim,
    ) {}

    public function envelope(): Envelope
    {
        $assunto = match (true) {
            $this->diasParaFim > 1   => "Seu teste grátis termina em {$this->diasParaFim} dias",
            $this->diasParaFim === 1 => 'Seu teste grátis termina amanhã',
            default                  => 'Hoje é o último dia do seu teste grátis',
        };

        return new Envelope(subject: $assunto . ' — ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.plataforma.fim-do-teste');
    }
}
