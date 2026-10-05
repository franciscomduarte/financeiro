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

            $vencimento = Carbon::parse($cobranca->vencimento)->format('d/m/Y');
            $ok = $whatsapp->enviarLembrete(
                telefone: $cobranca->paciente->telefone,
                nomePaciente: $cobranca->paciente->nome,
                valor: (float) $cobranca->valor,
                vencimento: $vencimento,
            );
            app(\App\Services\NotificacaoService::class)->registrar(\App\Enums\TipoNotificacao::LembretePagamento, \App\Enums\CanalNotificacao::WhatsApp, $cobranca->paciente, (string) $cobranca->paciente->telefone, 'Lembrete de pagamento',
                'Cobrança de R$ ' . number_format((float) $cobranca->valor, 2, ',', '.') . ' com vencimento em ' . $vencimento, $ok, $cobranca, $whatsapp->ultimoIdMensagem);
        }
    }
}
