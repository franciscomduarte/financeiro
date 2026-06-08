<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\StatusAgendamento;
use App\Enums\TipoNotificacaoAgendamento;
use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarLembretesAgendamentosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function handle(): void
    {
        $statusAtivos = [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value];
        $now          = now();

        // Lembrete 1 dia: início entre now+23h e now+25h, lembrete ainda não enviado
        $lembrete1Dia = Agendamento::whereIn('status', $statusAtivos)
            ->whereNull('lembrete_1dia_em')
            ->whereBetween('inicio_em', [$now->copy()->addHours(23), $now->copy()->addHours(25)])
            ->get(['id']);

        foreach ($lembrete1Dia as $ag) {
            $ag->update(['lembrete_1dia_em' => $now]);
            NotificacaoAgendamentoJob::dispatch($ag->id, TipoNotificacaoAgendamento::Lembrete1Dia)
                ->onQueue('default');
        }

        Log::info("EnviarLembretesAgendamentosJob: {$lembrete1Dia->count()} lembrete(s) 1 dia enviado(s)");

        // Lembrete 2 horas: início entre now+110min e now+130min, lembrete ainda não enviado
        $lembrete2Horas = Agendamento::whereIn('status', $statusAtivos)
            ->whereNull('lembrete_2horas_em')
            ->whereBetween('inicio_em', [$now->copy()->addMinutes(110), $now->copy()->addMinutes(130)])
            ->get(['id']);

        foreach ($lembrete2Horas as $ag) {
            $ag->update(['lembrete_2horas_em' => $now]);
            NotificacaoAgendamentoJob::dispatch($ag->id, TipoNotificacaoAgendamento::Lembrete2Horas)
                ->onQueue('default');
        }

        Log::info("EnviarLembretesAgendamentosJob: {$lembrete2Horas->count()} lembrete(s) 2 horas enviado(s)");
    }
}
