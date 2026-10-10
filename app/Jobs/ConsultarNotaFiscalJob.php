<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\NotaFiscal\AtualizarSituacaoNotaFiscalAction;
use App\Enums\StatusNotaFiscal;
use App\Models\NotaFiscal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Consulta a nota na Focus NFe até a prefeitura responder. Repete com intervalos crescentes
 * (até ~2 horas). Depois disso, o aviso automático da Focus e a varredura periódica
 * (RevisarNotasProcessandoAction) continuam acompanhando a nota.
 */
class ConsultarNotaFiscalJob implements ShouldQueue
{
    use Queueable;

    public const MAX_CONSULTAS = 12;

    public int $tries = 3;

    /** @var array<int, int> Focus/prefeitura instável (erro 5xx): espera antes de tentar de novo */
    public array $backoff = [60, 300];

    public function __construct(public readonly string $notaId) {}

    public function handle(AtualizarSituacaoNotaFiscalAction $atualizar): void
    {
        $nota = NotaFiscal::query()->find($this->notaId);
        if ($nota === null || $nota->status !== StatusNotaFiscal::Processando) {
            return;
        }

        $nota = $atualizar->execute($nota);

        if ($nota->status === StatusNotaFiscal::Processando && $nota->consultas < self::MAX_CONSULTAS) {
            // 15s, 30s, 1min, 2min, 4min... (máximo 20 min entre consultas)
            $espera = min(1200, 15 * (2 ** max(0, $nota->consultas - 1)));
            self::dispatch($nota->id)->onQueue('default')->delay(now()->addSeconds($espera));
        }
    }
}
