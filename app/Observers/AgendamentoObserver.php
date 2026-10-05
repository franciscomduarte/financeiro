<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Agendamento;
use App\Services\NotificacaoService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mantém os lembretes do agendamento em dia, venha a mudança de onde vier (tela, API, importação):
 * cria ao marcar, refaz ao mudar o horário e cancela quando deixa de estar pendente.
 */
class AgendamentoObserver
{
    public function __construct(private readonly NotificacaoService $notificacoes) {}

    public function created(Agendamento $agendamento): void
    {
        $this->seguro($agendamento, fn () => $this->notificacoes->agendarLembretes($agendamento));
    }

    public function updated(Agendamento $agendamento): void
    {
        $this->seguro($agendamento, function () use ($agendamento): void {
            if (! $agendamento->status->isPendente()) {
                if ($agendamento->wasChanged('status')) {
                    $this->notificacoes->cancelarLembretes($agendamento, 'Agendamento ' . mb_strtolower($agendamento->status->label()) . '.');
                }

                return;
            }

            if ($agendamento->wasChanged('inicio_em')) {
                $this->notificacoes->cancelarLembretes($agendamento, 'O horário mudou.');
                $this->notificacoes->agendarLembretes($agendamento);
            } elseif ($agendamento->wasChanged('status')) {
                $this->notificacoes->agendarLembretes($agendamento); // voltou a ficar pendente
            }
        });
    }

    /** Lembrete nunca impede salvar o agendamento; a falha fica no log. */
    private function seguro(Agendamento $agendamento, callable $acao): void
    {
        try {
            $acao();
        } catch (Throwable $e) {
            Log::error('[Notificações] falha ao atualizar lembretes', ['agendamento_id' => $agendamento->id, 'erro' => $e->getMessage()]);
        }
    }
}
