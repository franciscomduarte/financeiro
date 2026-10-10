<?php

declare(strict_types=1);

namespace App\Actions\NotaFiscal;

use App\Enums\StatusNotaFiscal;
use App\Jobs\ConsultarNotaFiscalJob;
use App\Jobs\EnviarNotaFiscalJob;
use App\Models\NotaFiscal;
use Illuminate\Support\Facades\Log;

/**
 * Varredura periódica (rodar com a clínica ativa): nenhuma nota fica esquecida em "processando".
 * - nunca consultada há mais de 10 min: reenvia (o envio é idempotente pela referência);
 * - sem consulta há mais de 30 min: consulta de novo;
 * - processando há mais de 48 h: vira erro, com orientação.
 */
class RevisarNotasProcessandoAction
{
    public const HORAS_ATE_DESISTIR = 48;

    private const LIMITE = 200;

    /** @return array{reenviadas: int, consultadas: int, expiradas: int} */
    public function execute(): array
    {
        $total = ['reenviadas' => 0, 'consultadas' => 0, 'expiradas' => 0];

        NotaFiscal::query()->select(['id', 'status', 'consultas', 'ultima_consulta_em', 'created_at'])
            ->where('status', StatusNotaFiscal::Processando)
            ->where('created_at', '<', now()->subMinutes(10))
            ->orderBy('created_at')->limit(self::LIMITE)->get()
            ->each(function (NotaFiscal $nota) use (&$total): void {
                if ($nota->created_at->lt(now()->subHours(self::HORAS_ATE_DESISTIR))) {
                    $nota->update([
                        'status'        => StatusNotaFiscal::Erro,
                        'mensagem_erro' => 'A prefeitura não respondeu em ' . self::HORAS_ATE_DESISTIR . ' horas. Confira a nota no painel da Focus NFe '
                            . 'antes de emitir de novo, para não duplicar.',
                    ]);
                    $total['expiradas']++;
                } elseif ($nota->consultas === 0) {
                    EnviarNotaFiscalJob::dispatch($nota->id)->onQueue('default');
                    $total['reenviadas']++;
                } elseif ($nota->ultima_consulta_em === null || $nota->ultima_consulta_em->lt(now()->subMinutes(30))) {
                    ConsultarNotaFiscalJob::dispatch($nota->id)->onQueue('default');
                    $total['consultadas']++;
                }
            });

        if (array_sum($total) > 0) {
            Log::info('[NFS-e] varredura de notas em processamento', $total);
        }

        return $total;
    }
}
