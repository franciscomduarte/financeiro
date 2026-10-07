<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Lead;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Avisa a equipe no "WhatsApp da gestão" sobre um lead atendido pelo assistente (agendou, pediu atendimento humano). */
class AvisarEquipeLeadJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public readonly string $leadId, public readonly string $texto) {}

    public function handle(WhatsAppService $whatsapp, ClinicaAtual $clinicaAtual): void
    {
        $clinica = $clinicaAtual->get();
        if ($clinica === null || blank($clinica->whatsapp_numero) || ! $clinica->whatsappConfigurado() || ! Lead::query()->whereKey($this->leadId)->exists()) {
            return;
        }

        $mensagem = $this->texto . "\n\n" . route('leads.index', ['lead' => $this->leadId]);
        if (! $whatsapp->enviarTextoParaTelefone((string) $clinica->whatsapp_numero, $mensagem)) {
            Log::warning('[Assistente] aviso à equipe não enviado', ['lead_id' => $this->leadId, 'tenant_id' => $clinica->id]);
        }
    }
}
