<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Clinica;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso ao dono da plataforma: nova clínica cadastrada pelo "Assine já". */
class NovaClinicaCadastradaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Clinica $clinica,
        public readonly User $responsavel,
        public readonly string $celular,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '🎉 Nova clínica em teste: ' . $this->clinica->nome);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.plataforma.nova-clinica');
    }
}
