<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\NotaFiscal\MontarNotaFocus;
use App\Enums\StatusNotaFiscal;
use App\Models\NotaFiscal;
use App\Services\FocusNfeService;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Envia a NFS-e para a Focus NFe e agenda a consulta do resultado. */
class EnviarNotaFiscalJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(public readonly string $notaId) {}

    public function handle(FocusNfeService $focus, MontarNotaFocus $montar, ClinicaAtual $clinicaAtual): void
    {
        $nota = NotaFiscal::query()->find($this->notaId);
        if ($nota === null || $nota->status !== StatusNotaFiscal::Processando) {
            return;
        }

        $resultado = $focus->emitir($nota->padrao, $nota->referencia, $montar->montar($nota, $clinicaAtual->get()));

        if ($resultado['status'] === 'erro_autorizacao') {
            $nota->update(['status' => StatusNotaFiscal::Erro, 'mensagem_erro' => $resultado['mensagem'] ?? 'A Focus NFe recusou a nota.']);
            Log::warning('[NFS-e] recusada no envio', ['nota_id' => $nota->id, 'mensagem' => $resultado['mensagem']]);

            return;
        }

        ConsultarNotaFiscalJob::dispatch($nota->id)->onQueue('default')->delay(now()->addSeconds(15));
    }

    /** Esgotou as tentativas (Focus fora do ar, token inválido...): a nota não fica parada em "processando". */
    public function failed(?Throwable $erro): void
    {
        $nota = NotaFiscal::query()->find($this->notaId);
        if ($nota === null || $nota->status !== StatusNotaFiscal::Processando || $nota->consultas > 0) {
            return; // já foi enviada e está sendo acompanhada
        }

        $nota->update([
            'status'        => StatusNotaFiscal::Erro,
            'mensagem_erro' => 'Não foi possível falar com a Focus NFe para enviar a nota. Confira o token em Dados da clínica › Nota fiscal e use "Corrigir e emitir de novo".',
        ]);
        Log::error('[NFS-e] envio falhou depois de todas as tentativas', ['nota_id' => $nota->id, 'erro' => $erro?->getMessage()]);
    }
}
