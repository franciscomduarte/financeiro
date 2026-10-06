<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Notificacoes\MontarMensagemAgendamento;
use App\Enums\CanalNotificacao;
use App\Enums\StatusNotificacao;
use App\Mail\NotificacaoMail;
use App\Models\Agendamento;
use App\Models\Notificacao;
use App\Services\NotificacaoService;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envia uma notificação. Textos de agendamento são montados na hora do envio (com o horário e o
 * texto configurado mais recentes); lembretes de horário que mudou ou já passou são cancelados.
 */
class EnviarNotificacaoJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1; // sem nova tentativa automática: evita mensagem duplicada; a tela permite reenviar
    public int $timeout = 60;

    public function __construct(public readonly string $notificacaoId) {}

    public function handle(WhatsAppService $whatsapp, NotificacaoService $notificacoes, MontarMensagemAgendamento $montar, ClinicaAtual $clinicaAtual): void
    {
        $n = Notificacao::query()->find($this->notificacaoId);
        if ($n === null || ! in_array($n->status, [StatusNotificacao::Agendada, StatusNotificacao::Enviando], true)) {
            return;
        }

        $agendamento = $n->origem_type === Agendamento::class
            ? Agendamento::query()->with(['paciente:id,nome,telefone,email', 'profissional:id,nome', 'procedimento:id,nome'])->find($n->origem_id)
            : null;

        if ($n->tipo->lembrete() && ($agendamento === null || ! $agendamento->status->isPendente() || $agendamento->inicioReal()->isPast())) {
            $n->update(['status' => StatusNotificacao::Cancelada, 'erro' => 'O agendamento mudou ou o horário já passou.']);

            return;
        }

        if ($agendamento !== null && $n->tipo->configuravel()) {
            $msg = $montar->montar($agendamento, $notificacoes->configuracao($n->tipo));
            $destino = $n->canal === CanalNotificacao::WhatsApp ? $agendamento->paciente?->telefone : $agendamento->paciente?->email;
            $n->fill(['assunto' => $msg['assunto'], 'conteudo' => $msg['texto'], 'destino' => $destino ?: $n->destino]);
        }

        $n->tentativas++;

        if (blank($n->destino) || blank($n->conteudo)) {
            $this->falhou($n, blank($n->destino) ? 'Paciente sem ' . ($n->canal === CanalNotificacao::WhatsApp ? 'telefone' : 'e-mail') . ' no cadastro.' : 'Mensagem sem texto.');

            return;
        }

        try {
            if ($n->canal === CanalNotificacao::WhatsApp) {
                if (! $clinicaAtual->get()?->whatsappConfigurado()) {
                    $this->falhou($n, 'WhatsApp da clínica não configurado (Dados da clínica › Integrações).');

                    return;
                }
                if (! $whatsapp->enviarTextoParaTelefone((string) $n->destino, (string) $n->conteudo)) {
                    $this->falhou($n, 'O WhatsApp não aceitou a mensagem. Confira se o número tem WhatsApp e se a conexão da clínica está ativa.');

                    return;
                }
                $n->id_externo = $whatsapp->ultimoIdMensagem;
            } else {
                Mail::to((string) $n->destino)->send(new NotificacaoMail($n->id, (string) $n->assunto, (string) $n->conteudo));
            }
        } catch (Throwable $e) {
            Log::error('[Notificações] falha no envio', ['notificacao_id' => $n->id, 'canal' => $n->canal->value, 'erro' => $e->getMessage()]);
            $this->falhou($n, 'Falha no envio: ' . mb_substr($e->getMessage(), 0, 300));

            return;
        }

        $n->fill(['status' => StatusNotificacao::Enviada, 'enviada_em' => now(), 'erro' => null])->save();
    }

    private function falhou(Notificacao $n, string $motivo): void
    {
        $n->fill(['status' => StatusNotificacao::Falhou, 'erro' => $motivo])->save();
        Log::warning('[Notificações] não enviada', ['notificacao_id' => $n->id, 'tipo' => $n->tipo->value, 'motivo' => $motivo]);
    }
}
