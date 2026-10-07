<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\EtapaLead;
use App\Enums\OrigemLead;
use App\Enums\TipoInteracaoLead;
use App\Jobs\AvisarNovoLeadJob;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Models\Procedimento;
use App\Support\Telefone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Cria o lead (tela, formulário público ou WhatsApp). Se já existe um lead em aberto com o mesmo
 * telefone (ou e-mail), não duplica: registra a nova entrada no histórico dele.
 */
class CriarLeadAction
{
    /**
     * @param  array{nome: string, telefone?: ?string, email?: ?string, procedimento_id?: int|string|null, interesse?: ?string,
     *               observacoes?: ?string, responsavel_id?: ?int, proximo_contato_em?: ?string, consentimento?: bool}  $dados
     * @return array{0: Lead, 1: bool}  [lead, foi criado agora]
     */
    public function execute(array $dados, OrigemLead $origem, TipoInteracaoLead $entrada = TipoInteracaoLead::Criado, ?string $texto = null, bool $avisarEquipe = false, ?string $mensagemId = null): array
    {
        $nome     = mb_substr(trim((string) $dados['nome']), 0, 150);
        $chave    = Telefone::chave($dados['telefone'] ?? null);
        $email    = filled($dados['email'] ?? null) ? mb_strtolower(trim((string) $dados['email'])) : null;
        $texto    = filled($texto) ? mb_substr(trim($texto), 0, 2000) : null;

        if ($nome === '') {
            throw new InvalidArgumentException('Informe o nome.');
        }
        $lid      = filled($dados['whatsapp_lid'] ?? null) ? mb_substr((string) $dados['whatsapp_lid'], 0, 40) : null;

        if ($chave === null && $email === null && $lid === null) {
            throw new InvalidArgumentException('Informe um telefone com DDD ou um e-mail.');
        }

        return DB::transaction(function () use ($dados, $origem, $entrada, $texto, $avisarEquipe, $nome, $chave, $email, $lid, $mensagemId): array {
            $existente = Lead::query()
                ->whereIn('etapa', array_map(fn ($e) => $e->value, EtapaLead::abertas()))
                ->where(fn ($q) => $q->when($chave, fn ($w) => $w->where('telefone_chave', $chave))
                    ->when($email, fn ($w) => $w->orWhere('email', $email))
                    ->when($lid, fn ($w) => $w->orWhere('whatsapp_lid', $lid)))
                ->lockForUpdate()
                ->first();

            if ($existente !== null) {
                $this->interacao($existente, $entrada, $texto ?? 'Novo contato pelo canal ' . $origem->label() . '.', $mensagemId);
                $existente->update(['ultima_interacao_em' => now()]);

                return [$existente, false];
            }

            $procedimentoId = filled($dados['procedimento_id'] ?? null) && Procedimento::query()->whereKey((int) $dados['procedimento_id'])->exists()
                ? (int) $dados['procedimento_id'] : null;

            $lead = Lead::create([
                'nome'                => $nome,
                'telefone'            => Telefone::formatar($dados['telefone'] ?? null),
                'telefone_chave'      => $chave,
                'whatsapp_lid'        => $lid,
                'email'               => $email,
                'origem'              => $origem,
                'procedimento_id'     => $procedimentoId,
                'interesse'           => filled($dados['interesse'] ?? null) ? mb_substr(trim((string) $dados['interesse']), 0, 255) : null,
                'etapa'               => EtapaLead::Novo,
                'responsavel_id'      => $dados['responsavel_id'] ?? null,
                'proximo_contato_em'  => filled($dados['proximo_contato_em'] ?? null) ? $dados['proximo_contato_em'] : null,
                'observacoes'         => filled($dados['observacoes'] ?? null) ? mb_substr(trim((string) $dados['observacoes']), 0, 5000) : null,
                'consentimento_em'    => ! empty($dados['consentimento']) ? now() : null,
                'ultima_interacao_em' => now(),
            ]);

            $this->interacao($lead, $entrada, $texto, $mensagemId);
            if ($paciente = BuscarPacienteDoLead::porContato($chave, $email)) {
                $this->interacao($lead, TipoInteracaoLead::Nota, "Telefone/e-mail já cadastrado como paciente: {$paciente->nome}.");
            }

            if ($avisarEquipe) {
                AvisarNovoLeadJob::dispatch($lead->id)->onQueue('default')->afterCommit();
            }

            Log::info('[Leads] lead criado', ['lead_id' => $lead->id, 'origem' => $origem->value, 'user_id' => auth()->id()]);

            return [$lead, true];
        });
    }

    private function interacao(Lead $lead, TipoInteracaoLead $tipo, ?string $texto, ?string $mensagemId = null): void
    {
        LeadInteracao::create(['lead_id' => $lead->id, 'user_id' => auth()->id(), 'tipo' => $tipo, 'texto' => $texto, 'mensagem_id' => $mensagemId]);
    }
}
