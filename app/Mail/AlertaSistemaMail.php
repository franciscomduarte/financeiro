<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alerta para o dono da plataforma: problema novo ou sistema normalizado. */
class AlertaSistemaMail extends Mailable
{
    /** @param  array<string, string>  $problemas  @param  array<int, string>  $normalizados */
    public function __construct(public readonly array $problemas, public readonly array $normalizados) {}

    public function envelope(): Envelope
    {
        $app = (string) config('app.name');

        return new Envelope(subject: $this->problemas !== []
            ? "⚠️ {$app}: " . count($this->problemas) . ' problema(s) no sistema'
            : "✅ {$app}: sistema normalizado");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.plataforma.alerta-sistema');
    }
}
