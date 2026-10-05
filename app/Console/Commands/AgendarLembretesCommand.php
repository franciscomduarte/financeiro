<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Agendamento;
use App\Services\NotificacaoService;
use App\Support\ClinicaAtual;
use Illuminate\Console\Command;

/**
 * Cria os lembretes agendados dos horários futuros já marcados (rodar uma vez após o deploy da
 * central de notificações). Idempotente: não duplica lembretes existentes.
 */
class AgendarLembretesCommand extends Command
{
    protected $signature = 'notificacoes:agendar-lembretes';

    protected $description = 'Agenda os lembretes dos agendamentos futuros de todas as clínicas';

    public function handle(ClinicaAtual $clinicaAtual, NotificacaoService $notificacoes): int
    {
        $total = 0;

        $clinicaAtual->paraCadaClinica(function () use ($notificacoes, &$total): void {
            $notificacoes->esquecerConfiguracoes();
            Agendamento::query()
                ->select(['id', 'tenant_id', 'paciente_id', 'profissional_id', 'procedimento_id', 'inicio_em', 'status', 'lembrete_1dia_em', 'lembrete_2horas_em'])
                ->whereIn('status', ['agendado', 'confirmado'])
                ->where('inicio_em', '>', now())
                ->lazyById(200)
                ->each(function (Agendamento $a) use ($notificacoes, &$total): void {
                    $total += $notificacoes->agendarLembretes($a);
                });
        });

        $this->info("{$total} lembrete(s) agendado(s).");

        return self::SUCCESS;
    }
}
