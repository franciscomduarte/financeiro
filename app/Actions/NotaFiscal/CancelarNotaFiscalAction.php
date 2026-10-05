<?php

declare(strict_types=1);

namespace App\Actions\NotaFiscal;

use App\Enums\StatusNotaFiscal;
use App\Models\NotaFiscal;
use App\Services\FocusNfeService;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Cancela uma NFS-e emitida (a prefeitura exige justificativa de pelo menos 15 caracteres). */
class CancelarNotaFiscalAction
{
    public function __construct(
        private readonly FocusNfeService $focus,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    public function execute(string $notaId, string $justificativa): NotaFiscal
    {
        $this->clinicaAtual->garantirEscrita();

        $justificativa = trim($justificativa);
        if (mb_strlen($justificativa) < 15) {
            throw new RuntimeException('Explique o motivo do cancelamento com pelo menos 15 caracteres.');
        }

        $nota = NotaFiscal::query()->findOrFail($notaId);
        if ($nota->status !== StatusNotaFiscal::Autorizada) {
            throw new RuntimeException('Só notas emitidas podem ser canceladas.');
        }

        $r = $this->focus->cancelar($nota->referencia, mb_substr($justificativa, 0, 255));
        if ($r['status'] !== 'cancelado') {
            throw new RuntimeException('A prefeitura não cancelou a nota: ' . ($r['mensagem'] ?? 'tente de novo em instantes.'));
        }

        $nota->update([
            'status'                     => StatusNotaFiscal::Cancelada,
            'cancelada_em'               => now(),
            'justificativa_cancelamento' => mb_substr($justificativa, 0, 255),
        ]);

        Log::warning('[NFS-e] nota cancelada', ['nota_id' => $nota->id, 'user_id' => auth()->id()]);

        return $nota;
    }
}
