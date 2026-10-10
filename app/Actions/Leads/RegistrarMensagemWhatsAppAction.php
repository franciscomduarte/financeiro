<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\EtapaLead;
use App\Enums\OrigemLead;
use App\Enums\TipoInteracaoLead;
use App\Models\Lead;
use App\Jobs\ResponderLeadJob;
use App\Jobs\ResponderPacienteJob;
use App\Models\AssistenteConfiguracao;
use App\Models\LeadInteracao;
use App\Support\Telefone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Mensagem no WhatsApp da clínica (rodar com a clínica ativa).
 * Recebida: número desconhecido vira lead "Novo" (com aviso à equipe); lead em aberto ganha a mensagem na conversa;
 * paciente não vira lead: a conversa fica na ficha dele.
 * Enviada pela clínica (celular ou WhatsApp Web): entra na conversa do lead em aberto (conta como contato feito) ou na ficha do paciente.
 */
class RegistrarMensagemWhatsAppAction
{
    public function __construct(
        private readonly CriarLeadAction $criar,
        private readonly \App\Actions\Pacientes\RegistrarMensagemPacienteAction $registrarPaciente,
    ) {}

    /** @return string o que foi feito: lead_criado | lead_atualizado | resposta_registrada | duplicada | paciente | paciente_resposta | ignorado */
    public function execute(?string $telefone, ?string $nome, string $texto, ?string $lid = null, ?string $mensagemId = null, bool $daClinica = false): string
    {
        $chave = Telefone::chave($telefone);
        if ($chave === null && blank($lid)) {
            return 'ignorado';
        }
        $mensagemId = filled($mensagemId) ? mb_substr($mensagemId, 0, 100) : null;
        if ($mensagemId !== null && LeadInteracao::query()->where('mensagem_id', $mensagemId)->exists()) {
            return 'duplicada';
        }
        $texto = mb_substr(trim($texto), 0, 2000);

        // Lead em aberto vem primeiro: quem agendou pelo assistente já virou paciente e continua a conversa no lead
        [$resultado, $leadId, $interacaoId] = DB::transaction(function () use ($chave, $telefone, $lid, $texto, $mensagemId, $daClinica): array {
            $lead = Lead::query()
                ->where(fn ($q) => $q->when($chave, fn ($w) => $w->where('telefone_chave', $chave))
                    ->when(filled($lid), fn ($w) => $w->orWhere('whatsapp_lid', $lid)))
                ->whereIn('etapa', array_map(fn ($e) => $e->value, EtapaLead::abertas()))
                ->lockForUpdate()->first();

            if ($lead === null) {
                return [null, null, null];
            }

            $tipo = $daClinica ? TipoInteracaoLead::WhatsAppEnviado : TipoInteracaoLead::WhatsAppRecebido;
            $dados = ['lead_id' => $lead->id, 'tipo' => $tipo, 'texto' => $texto];
            $interacao = $mensagemId !== null ? LeadInteracao::createOrFirst(['mensagem_id' => $mensagemId], $dados) : LeadInteracao::create($dados);

            $atualizar = ['ultima_interacao_em' => now()];
            if (filled($lid) && $lead->whatsapp_lid === null) {
                $atualizar['whatsapp_lid'] = $lid;
            }
            if ($chave !== null && $lead->telefone_chave === null) {
                $atualizar += ['telefone' => Telefone::formatar($telefone), 'telefone_chave' => $chave];
            }
            if ($daClinica) {
                $atualizar['primeiro_contato_em'] = $lead->primeiro_contato_em ?? now();
                if ($lead->etapa === EtapaLead::Novo) {
                    $atualizar['etapa'] = EtapaLead::EmContato;
                }
                // A equipe respondeu (celular ou WhatsApp Web): o assistente sai da conversa. O eco das mensagens do próprio assistente não conta.
                if ($lead->assistente_pausado_em === null && ! Cache::has(ResponderLeadAction::chaveEnviando($lead->id))) {
                    $atualizar += ['assistente_pausado_em' => now(), 'assistente_motivo' => 'A equipe respondeu pelo WhatsApp.'];
                }
            }
            $lead->update($atualizar);

            return [$daClinica ? 'resposta_registrada' : 'lead_atualizado', $lead->id, $interacao->id];
        });

        if ($resultado !== null) {
            if (! $daClinica) {
                $this->agendarResposta($leadId, $interacaoId);
            }

            return $resultado;
        }
        // Quem já é paciente não vira lead: a conversa (nos dois sentidos) fica na ficha
        $pacienteId = $this->pacienteDoContato($chave, $lid);
        if ($pacienteId !== null) {
            $mensagem = $this->registrarPaciente->execute($pacienteId, $texto, $daClinica, $mensagemId);
            if (! $daClinica && $mensagem->wasRecentlyCreated && AssistenteConfiguracao::query()->where('ativo', true)->exists()) {
                // O assistente avisa que a equipe vai responder (junta mensagens seguidas, como nos leads)
                ResponderPacienteJob::dispatch($pacienteId, $mensagem->id)
                    ->delay(now()->addSeconds((int) config('services.anthropic.espera_assistente', 15)))
                    ->afterCommit();
            }

            return $daClinica ? 'paciente_resposta' : 'paciente';
        }
        if ($daClinica) {
            return 'ignorado'; // conversa da clínica com quem não é lead nem paciente
        }

        [$lead] = $this->criar->execute(
            ['nome' => filled($nome) ? $nome : 'Contato do WhatsApp', 'telefone' => $telefone, 'whatsapp_lid' => $lid],
            OrigemLead::WhatsApp, TipoInteracaoLead::WhatsAppRecebido, $texto, avisarEquipe: true, mensagemId: $mensagemId,
        );
        $interacaoId = LeadInteracao::query()->where('lead_id', $lead->id)->where('tipo', TipoInteracaoLead::WhatsAppRecebido)
            ->latest('created_at')->orderByDesc('id')->value('id');
        if ($interacaoId !== null) {
            $this->agendarResposta($lead->id, $interacaoId);
        }

        return 'lead_criado';
    }

    /** Paciente pelo telefone da ficha ou pelo lead marcado como "Já é paciente" (que pode ter outro número). */
    private function pacienteDoContato(?string $chave, ?string $lid): ?string
    {
        if ($chave !== null && ($paciente = BuscarPacienteDoLead::porContato($chave, null)) !== null) {
            return $paciente->id;
        }

        return Lead::query()->where('etapa', EtapaLead::JaPaciente)
            ->whereHas('paciente', fn ($p) => $p->whereNull('anonimizado_em'))
            ->where(fn ($q) => $q->when($chave, fn ($w) => $w->where('telefone_chave', $chave))
                ->when(filled($lid), fn ($w) => $w->orWhere('whatsapp_lid', $lid)))
            ->latest('updated_at')->value('paciente_id');
    }

    /** Assistente ligado: responde depois de alguns segundos (junta mensagens seguidas). */
    private function agendarResposta(string $leadId, string $interacaoId): void
    {
        if (! AssistenteConfiguracao::query()->where('ativo', true)->exists()) {
            return;
        }

        ResponderLeadJob::dispatch($leadId, $interacaoId)
            ->delay(now()->addSeconds((int) config('services.anthropic.espera_assistente', 15)))
            ->afterCommit();
    }
}
