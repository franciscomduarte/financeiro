<?php

declare(strict_types=1);

namespace App\Actions\Agenda;

use App\Enums\OrigemConfirmacao;
use App\Enums\StatusAgendamento;
use App\Jobs\ResponderConfirmacaoJob;
use App\Models\Agendamento;
use App\Support\RespostaConfirmacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resposta do paciente ao pedido de confirmação da véspera (rodar com a clínica ativa).
 * SIM confirma o próximo horário que pediu confirmação; NÃO marca "pediu para remarcar" e avisa a equipe.
 * Devolve true quando a mensagem era a resposta (o assistente não responde de novo).
 */
class ConfirmarPeloWhatsAppAction
{
    /** @param string|array<int, string> $pacienteId a ficha do contato ou todas as fichas com o mesmo telefone */
    public function execute(string|array $pacienteId, string $texto): bool
    {
        $confirma = RespostaConfirmacao::interpretar($texto);
        if ($confirma === null) {
            return false;
        }

        $resultado = DB::transaction(function () use ($pacienteId, $texto, $confirma): ?array {
            $agendamento = Agendamento::query()->withoutGlobalScope(\App\Models\Scopes\ProfissionalScope::class)
                ->select(['id', 'tenant_id', 'paciente_id', 'inicio_em', 'status', 'confirmacao_pedida_em', 'confirmado_em', 'confirmacao_origem', 'pediu_remarcar_em'])
                ->whereIn('paciente_id', (array) $pacienteId)
                ->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
                // Pedido registrado no agendamento ou, por segurança, o lembrete da véspera que saiu no WhatsApp
                ->where(fn ($q) => $q->whereNotNull('confirmacao_pedida_em')
                    ->orWhereExists(fn ($n) => $n->selectRaw('1')->from('notificacoes')
                        ->whereColumn('notificacoes.tenant_id', 'agendamentos.tenant_id')
                        ->whereRaw('notificacoes.origem_id = agendamentos.id::text')
                        ->where('notificacoes.origem_type', Agendamento::class)
                        ->where('notificacoes.tipo', \App\Enums\TipoNotificacao::LembreteVespera->value)
                        ->where('notificacoes.canal', \App\Enums\CanalNotificacao::WhatsApp->value)
                        ->whereIn('notificacoes.status', ['enviada', 'entregue', 'lida'])))
                ->where('inicio_em', '>', now())
                ->orderBy('inicio_em')
                ->lockForUpdate()->first();
            if ($agendamento === null) {
                return null;
            }

            $resposta = mb_substr(trim($texto), 0, 500);
            if ($confirma) {
                if ($agendamento->status === StatusAgendamento::Confirmado) {
                    return ['id' => $agendamento->id, 'acao' => null]; // já confirmado: não repete o agradecimento
                }
                $agendamento->update([
                    'status' => StatusAgendamento::Confirmado->value, 'confirmado_em' => now(),
                    'confirmacao_origem' => OrigemConfirmacao::Paciente, 'pediu_remarcar_em' => null, 'resposta_confirmacao' => $resposta,
                ]);

                return ['id' => $agendamento->id, 'acao' => 'confirmou'];
            }

            if ($agendamento->pediu_remarcar_em !== null) {
                return ['id' => $agendamento->id, 'acao' => null];
            }
            $agendamento->update([
                'status' => StatusAgendamento::Agendado->value, 'confirmado_em' => null, 'confirmacao_origem' => null,
                'pediu_remarcar_em' => now(), 'resposta_confirmacao' => $resposta,
            ]);

            return ['id' => $agendamento->id, 'acao' => 'remarcar'];
        });

        if ($resultado === null) {
            Log::info('[Agenda] resposta SIM/NÃO sem horário aguardando confirmação', ['paciente_id' => $pacienteId]);

            return false;
        }
        if ($resultado['acao'] !== null) {
            ResponderConfirmacaoJob::dispatch($resultado['id'], $resultado['acao'] === 'confirmou')->afterCommit();
            Log::info('[Agenda] resposta à confirmação', ['agendamento_id' => $resultado['id'], 'acao' => $resultado['acao']]);
        }

        return true;
    }
}
