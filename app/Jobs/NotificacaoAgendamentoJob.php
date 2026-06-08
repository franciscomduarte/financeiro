<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TipoNotificacaoAgendamento;
use App\Mail\AgendamentoCanceladoMail;
use App\Mail\AgendamentoCriadoMail;
use App\Mail\AgendamentoLembreteMail;
use App\Mail\AgendamentoReagendadoMail;
use App\Models\Agendamento;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificacaoAgendamentoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly string $agendamentoId,
        public readonly TipoNotificacaoAgendamento $tipo,
    ) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        $agendamento = Agendamento::with(['paciente', 'profissional', 'procedimento'])
            ->find($this->agendamentoId);

        if (! $agendamento) {
            Log::warning('NotificacaoAgendamentoJob: agendamento não encontrado', ['id' => $this->agendamentoId]);
            return;
        }

        $paciente = $agendamento->paciente;

        // WhatsApp
        if ($paciente->telefone) {
            try {
                match ($this->tipo) {
                    TipoNotificacaoAgendamento::Confirmacao   => $whatsapp->enviarConfirmacaoAgendamento($agendamento),
                    TipoNotificacaoAgendamento::Cancelamento  => $whatsapp->enviarCancelamentoAgendamento($agendamento),
                    TipoNotificacaoAgendamento::Reagendamento => $whatsapp->enviarReagendamentoAgendamento($agendamento),
                    TipoNotificacaoAgendamento::Lembrete1Dia  => $whatsapp->enviarLembreteAgendamento($agendamento, 'amanhã'),
                    TipoNotificacaoAgendamento::Lembrete2Horas => $whatsapp->enviarLembreteAgendamento($agendamento, '2 horas'),
                };
                $agendamento->update(['whatsapp_enviado_em' => now()]);
            } catch (Throwable $e) {
                Log::error('NotificacaoAgendamentoJob: falha WhatsApp', [
                    'agendamento' => $this->agendamentoId,
                    'tipo'        => $this->tipo->value,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        // Email
        if ($paciente->email) {
            try {
                $mailable = match ($this->tipo) {
                    TipoNotificacaoAgendamento::Confirmacao    => new AgendamentoCriadoMail($agendamento),
                    TipoNotificacaoAgendamento::Cancelamento   => new AgendamentoCanceladoMail($agendamento),
                    TipoNotificacaoAgendamento::Reagendamento  => new AgendamentoReagendadoMail($agendamento),
                    TipoNotificacaoAgendamento::Lembrete1Dia   => new AgendamentoLembreteMail($agendamento, 'amanhã'),
                    TipoNotificacaoAgendamento::Lembrete2Horas => new AgendamentoLembreteMail($agendamento, '2 horas'),
                };
                Mail::to($paciente->email)->send($mailable);
                $agendamento->update(['email_enviado_em' => now()]);
            } catch (Throwable $e) {
                Log::error('NotificacaoAgendamentoJob: falha email', [
                    'agendamento' => $this->agendamentoId,
                    'tipo'        => $this->tipo->value,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }
}
