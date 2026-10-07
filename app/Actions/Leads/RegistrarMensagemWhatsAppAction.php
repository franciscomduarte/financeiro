<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\EtapaLead;
use App\Enums\OrigemLead;
use App\Enums\TipoInteracaoLead;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Support\Telefone;
use Illuminate\Support\Facades\DB;

/**
 * Mensagem no WhatsApp da clínica (rodar com a clínica ativa).
 * Recebida: número desconhecido vira lead "Novo" (com aviso à equipe); lead em aberto ganha a mensagem na conversa; paciente é ignorado.
 * Enviada pela clínica (celular ou WhatsApp Web): entra na conversa do lead em aberto e conta como contato feito.
 */
class RegistrarMensagemWhatsAppAction
{
    public function __construct(private readonly CriarLeadAction $criar) {}

    /** @return string o que foi feito: lead_criado | lead_atualizado | resposta_registrada | duplicada | paciente | ignorado */
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
        if (! $daClinica && $chave !== null && BuscarPacienteDoLead::porContato($chave, null) !== null) {
            return 'paciente';
        }

        $texto = mb_substr(trim($texto), 0, 2000);

        $resultado = DB::transaction(function () use ($chave, $telefone, $lid, $texto, $mensagemId, $daClinica): ?string {
            $lead = Lead::query()
                ->where(fn ($q) => $q->when($chave, fn ($w) => $w->where('telefone_chave', $chave))
                    ->when(filled($lid), fn ($w) => $w->orWhere('whatsapp_lid', $lid)))
                ->whereIn('etapa', array_map(fn ($e) => $e->value, EtapaLead::abertas()))
                ->lockForUpdate()->first();

            if ($lead === null) {
                return null;
            }

            $tipo = $daClinica ? TipoInteracaoLead::WhatsAppEnviado : TipoInteracaoLead::WhatsAppRecebido;
            $dados = ['lead_id' => $lead->id, 'tipo' => $tipo, 'texto' => $texto];
            $mensagemId !== null ? LeadInteracao::createOrFirst(['mensagem_id' => $mensagemId], $dados) : LeadInteracao::create($dados);

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
            }
            $lead->update($atualizar);

            return $daClinica ? 'resposta_registrada' : 'lead_atualizado';
        });

        if ($resultado !== null) {
            return $resultado;
        }
        if ($daClinica) {
            return 'ignorado'; // conversa da clínica com quem não é lead
        }

        $this->criar->execute(
            ['nome' => filled($nome) ? $nome : 'Contato do WhatsApp', 'telefone' => $telefone, 'whatsapp_lid' => $lid],
            OrigemLead::WhatsApp, TipoInteracaoLead::WhatsAppRecebido, $texto, avisarEquipe: true, mensagemId: $mensagemId,
        );

        return 'lead_criado';
    }
}
