<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\StatusNotificacao;
use App\Models\Notificacao;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** A cada minuto, por clínica: põe na fila as notificações agendadas que chegaram na hora. */
class DespacharNotificacoesJob implements ShouldQueue
{
    use Queueable;

    private const LOTE = 200;

    public function handle(): void
    {
        $vencidas = Notificacao::query()
            ->where('status', StatusNotificacao::Agendada)
            ->where('agendada_para', '<=', now())
            ->orderBy('agendada_para')
            ->limit(self::LOTE)
            ->pluck('id');

        foreach ($vencidas as $id) {
            // Troca de status atômica: se dois despachos rodarem juntos, só um envia
            $pegou = Notificacao::query()->whereKey($id)->where('status', StatusNotificacao::Agendada)
                ->update(['status' => StatusNotificacao::Enviando, 'updated_at' => now()]);
            if ($pegou === 1) {
                EnviarNotificacaoJob::dispatch($id)->onQueue('default');
            }
        }
    }
}
