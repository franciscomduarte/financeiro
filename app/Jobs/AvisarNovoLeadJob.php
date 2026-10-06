<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Lead;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Avisa a equipe no "WhatsApp da gestão" que chegou um lead (formulário ou WhatsApp). */
class AvisarNovoLeadJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public readonly string $leadId) {}

    public function handle(WhatsAppService $whatsapp, ClinicaAtual $clinicaAtual): void
    {
        $clinica = $clinicaAtual->get();
        $lead    = Lead::query()->with('procedimento:id,nome')->find($this->leadId);
        if ($lead === null || $clinica === null || blank($clinica->whatsapp_numero) || ! $clinica->whatsappConfigurado()) {
            return;
        }

        $interesse = $lead->procedimento?->nome ?? $lead->interesse;
        $texto = "🆕 *Novo lead — {$lead->origem->label()}*\n\n"
            . "👤 {$lead->nome}\n"
            . ($lead->telefone ? "📱 {$lead->telefone}\n" : '')
            . ($interesse ? "✨ Interesse: {$interesse}\n" : '')
            . "\nResponda rápido: quem fala primeiro costuma fechar. 😉\n" . route('leads.index', ['lead' => $lead->id]);

        if (! $whatsapp->enviarTextoParaTelefone((string) $clinica->whatsapp_numero, $texto)) {
            Log::warning('[Leads] aviso de novo lead não enviado', ['lead_id' => $lead->id, 'tenant_id' => $clinica->id]);
        }
    }
}
