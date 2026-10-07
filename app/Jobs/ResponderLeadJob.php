<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Leads\ResponderLeadAction;
use App\Enums\TipoInteracaoLead;
use App\Models\LeadInteracao;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Resposta do assistente a uma mensagem do lead. Sai com alguns segundos de espera: se o lead mandar
 * outra mensagem nesse meio-tempo, só o job da última responde (tudo de uma vez).
 */
class ResponderLeadJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(public readonly string $leadId, public readonly string $interacaoId) {}

    public function handle(ResponderLeadAction $responder): void
    {
        $ultima = LeadInteracao::query()->where('lead_id', $this->leadId)->where('tipo', TipoInteracaoLead::WhatsAppRecebido)
            ->latest('created_at')->orderByDesc('id')->value('id');
        if ($ultima !== $this->interacaoId) {
            return; // chegou mensagem mais nova: o job dela responde
        }

        // Um assistente por conversa de cada vez
        Cache::lock("assistente:lead:{$this->leadId}", 120)->block(30, function () use ($responder): void {
            $resultado = $responder->execute($this->leadId);
            Log::info('[Assistente] job concluído', ['lead_id' => $this->leadId, 'resultado' => $resultado]);
        });
    }
}
