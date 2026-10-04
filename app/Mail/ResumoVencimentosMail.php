<?php

declare(strict_types=1);

namespace App\Mail;

use App\DTOs\AlertaVencimento;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ResumoVencimentosMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, AlertaVencimento>  $vencidos
     * @param  Collection<int, AlertaVencimento>  $aVencer
     */
    public function __construct(
        public readonly Collection $vencidos,
        public readonly Collection $aVencer,
        public readonly CarbonImmutable $hoje,
    ) {}

    public function envelope(): Envelope
    {
        $partes = array_filter([
            $this->vencidos->isNotEmpty() ? $this->vencidos->count() . ' vencido(s)' : null,
            $this->aVencer->isNotEmpty() ? $this->aVencer->count() . ' a vencer' : null,
        ]);

        return new Envelope(
            subject: '📅 Vencimentos — ' . implode(', ', $partes) . ' (' . $this->hoje->format('d/m') . ')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.resumo-vencimentos');
    }
}
