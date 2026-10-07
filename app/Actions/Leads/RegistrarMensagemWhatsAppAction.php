<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\EtapaLead;
use App\Enums\OrigemLead;
use App\Enums\TipoInteracaoLead;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Support\Telefone;

/**
 * Mensagem recebida no WhatsApp da clínica (rodar com a clínica ativa). Número desconhecido vira
 * lead "Novo" (com aviso à equipe); lead em aberto ganha a mensagem no histórico; paciente é ignorado.
 */
class RegistrarMensagemWhatsAppAction
{
    /** Mensagens seguidas dentro deste intervalo não repetem no histórico. */
    private const AGRUPAR_MINUTOS = 10;

    public function __construct(private readonly CriarLeadAction $criar) {}

    /** @return string o que foi feito: lead_criado | lead_atualizado | paciente | ignorado */
    public function execute(string $telefone, ?string $nome, string $texto): string
    {
        $chave = Telefone::chave($telefone);
        if ($chave === null) {
            return 'ignorado';
        }
        if (BuscarPacienteDoLead::porContato($chave, null) !== null) {
            return 'paciente';
        }

        $texto = mb_substr(trim($texto), 0, 1000);
        $lead  = Lead::query()->where('telefone_chave', $chave)
            ->whereIn('etapa', array_map(fn ($e) => $e->value, EtapaLead::abertas()))->first();

        if ($lead !== null) {
            $recente = LeadInteracao::query()->where('lead_id', $lead->id)->where('tipo', TipoInteracaoLead::WhatsAppRecebido)
                ->where('created_at', '>=', now()->subMinutes(self::AGRUPAR_MINUTOS))->exists();
            if (! $recente) {
                LeadInteracao::create(['lead_id' => $lead->id, 'tipo' => TipoInteracaoLead::WhatsAppRecebido, 'texto' => $texto]);
            }
            $lead->update(['ultima_interacao_em' => now()]);

            return 'lead_atualizado';
        }

        $this->criar->execute(
            ['nome' => filled($nome) ? $nome : 'Contato do WhatsApp', 'telefone' => $telefone],
            OrigemLead::WhatsApp, TipoInteracaoLead::WhatsAppRecebido, $texto, avisarEquipe: true,
        );

        return 'lead_criado';
    }
}
