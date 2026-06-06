<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Cobranca;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LembreteVencimentoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WhatsAppService $whatsapp): void
    {
        $amanha = Carbon::tomorrow()->toDateString();

        $cobrancas = Cobranca::with('paciente')
            ->where('status', 'PENDING')
            ->whereDate('vencimento', $amanha)
            ->get();

        foreach ($cobrancas as $cobranca) {
            if (!$cobranca->paciente?->telefone) {
                continue;
            }

            $whatsapp->enviarLembrete(
                telefone: $cobranca->paciente->telefone,
                nomePaciente: $cobranca->paciente->nome,
                valor: (float) $cobranca->valor,
                vencimento: Carbon::parse($cobranca->vencimento)->format('d/m/Y'),
            );
        }
    }
}
