<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\EtapaLead;
use App\Enums\TipoInteracaoLead;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/** Resposta ao lead pelo WhatsApp da clínica (Evolution), registrada na conversa como contato feito. */
class EnviarWhatsAppLeadAction
{
    public function __construct(
        private readonly ClinicaAtual $clinicaAtual,
        private readonly WhatsAppService $whatsapp,
    ) {}

    public function execute(string $leadId, string $texto): LeadInteracao
    {
        $this->clinicaAtual->garantirEscrita();
        $texto = mb_substr(trim($texto), 0, 2000);
        if ($texto === '') {
            throw new InvalidArgumentException('Escreva a mensagem.');
        }
        if (! $this->clinicaAtual->get()?->whatsappConfigurado()) {
            throw new RuntimeException('O WhatsApp da clínica não está conectado. Configure em Dados da clínica.');
        }

        $lead = Lead::query()->select(['id', 'telefone', 'whatsapp_lid'])->findOrFail($leadId);
        $nacional = Telefone::nacional($lead->telefone);
        if ($nacional === null && blank($lead->whatsapp_lid)) {
            throw new InvalidArgumentException('Este lead não tem WhatsApp. Cadastre o telefone para responder.');
        }

        // Sem o número, o contato só é alcançável pelo código do WhatsApp (…@lid)
        try {
            $enviado = $nacional !== null
                ? $this->whatsapp->enviarTextoParaTelefone($nacional, $texto)
                : $this->whatsapp->enviarTexto($lead->whatsapp_lid . '@lid', $texto);
        } catch (ConnectionException $e) {
            Log::error('[Leads] Evolution fora do ar ao enviar WhatsApp', ['lead_id' => $lead->id, 'tenant_id' => $this->clinicaAtual->id(), 'erro' => $e->getMessage()]);

            throw new RuntimeException('O servidor do WhatsApp não respondeu. Tente de novo em instantes.');
        }
        if (! $enviado) {
            Log::warning('[Leads] falha ao enviar WhatsApp', ['lead_id' => $lead->id, 'tenant_id' => $this->clinicaAtual->id(), 'user_id' => auth()->id()]);

            throw new RuntimeException($nacional !== null
                ? 'O WhatsApp não aceitou a mensagem. Confira a conexão da clínica e tente de novo.'
                : 'O WhatsApp escondeu o número deste contato e não aceitou a mensagem. Responda pelo celular da clínica ou cadastre o telefone do lead.');
        }
        $mensagemId = $this->whatsapp->ultimoIdMensagem;

        return DB::transaction(function () use ($leadId, $texto, $mensagemId): LeadInteracao {
            $lead = Lead::query()->lockForUpdate()->findOrFail($leadId);
            $dados = ['lead_id' => $lead->id, 'user_id' => auth()->id(), 'tipo' => TipoInteracaoLead::WhatsAppEnviado, 'texto' => $texto];

            // O eco do webhook pode ter chegado antes: completa com o autor
            $interacao = $mensagemId !== null
                ? LeadInteracao::createOrFirst(['mensagem_id' => $mensagemId], $dados)
                : LeadInteracao::create($dados + ['mensagem_id' => 'sistema-' . str()->uuid()]);
            if ($interacao->user_id === null) {
                $interacao->update(['user_id' => auth()->id()]);
            }

            $atualizar = ['ultima_interacao_em' => now(), 'primeiro_contato_em' => $lead->primeiro_contato_em ?? now()];
            if ($lead->etapa === EtapaLead::Novo) {
                $atualizar['etapa'] = EtapaLead::EmContato;
            }
            $lead->update($atualizar);

            return $interacao;
        });
    }
}
