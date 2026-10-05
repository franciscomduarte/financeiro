<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\ClinicaAtual;
use App\Models\Paciente;
use App\Models\TransacaoAnexo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class AnexoTransacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Paciente $paciente,
        public readonly TransacaoAnexo $anexo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📎 Documento — ' . app(ClinicaAtual::class)->nome(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.anexo-transacao',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath(Storage::disk('local')->path($this->anexo->caminho))
                ->as($this->anexo->nome_arquivo)
                ->withMime($this->anexo->mime_type),
        ];
    }
}
