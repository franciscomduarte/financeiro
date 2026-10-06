<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\HtmlString;

/** E-mail genérico da central de notificações: assunto e texto já preenchidos. */
class NotificacaoMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $notificacaoId,
        public readonly string $assunto,
        public readonly string $texto,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->assunto);
    }

    /** A Brevo devolve "X-Mailin-custom" no webhook: é assim que a entrega/abertura volta para a notificação. */
    public function headers(): Headers
    {
        return new Headers(text: ['X-Mailin-custom' => 'notificacao:' . $this->notificacaoId]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notificacao', with: ['corpo' => self::formatar($this->texto)]);
    }

    /** Texto no estilo WhatsApp (*negrito*, _itálico_) para HTML seguro. */
    public static function formatar(string $texto): HtmlString
    {
        $html = e($texto);
        $html = preg_replace('/\*([^*\n]+)\*/u', '<strong>$1</strong>', $html) ?? $html;
        $html = preg_replace('/(?<![\w])_([^_\n]+)_(?![\w])/u', '<em>$1</em>', $html) ?? $html;

        return new HtmlString(nl2br($html, false));
    }
}
