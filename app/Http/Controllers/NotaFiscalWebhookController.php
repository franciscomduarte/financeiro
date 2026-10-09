<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StatusNotaFiscal;
use App\Jobs\ConsultarNotaFiscalJob;
use App\Models\Clinica;
use App\Models\NotaFiscal;
use App\Models\Scopes\ClinicaScope;
use App\Support\ClinicaAtual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Aviso da Focus NFe (gatilho) de que a situação de uma nota mudou. O corpo do aviso não é
 * usado como verdade: o sistema consulta a nota na Focus, como faria na consulta periódica.
 */
class NotaFiscalWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $referencia = (string) $request->input('ref', '');
        $nota = $referencia !== ''
            ? NotaFiscal::withoutGlobalScope(ClinicaScope::class)->select(['id', 'tenant_id', 'status'])->where('referencia', $referencia)->first()
            : null;
        if ($nota === null) {
            return response()->json(['status' => 'ignorado']); // nota de outro sistema na mesma conta da Focus
        }

        $clinica  = Clinica::query()->find($nota->tenant_id);
        $esperado = (string) $clinica?->nfse_webhook_token;
        $recebido = (string) ($request->header('Authorization') ?? '');
        if ($esperado === '' || ! hash_equals($esperado, preg_replace('/^(Bearer|Token)\s+/i', '', $recebido))) {
            Log::warning('[NFS-e] aviso da Focus com token inválido', ['tenant_id' => $nota->tenant_id, 'ip' => $request->ip()]);

            return response()->json(['status' => 'nao_autorizado'], 401);
        }

        if ($nota->status === StatusNotaFiscal::Processando) {
            app(ClinicaAtual::class)->executarComo($clinica, fn () => ConsultarNotaFiscalJob::dispatch($nota->id)->onQueue('default'));
        }
        Log::info('[NFS-e] aviso da Focus recebido', ['tenant_id' => $nota->tenant_id, 'nota_id' => $nota->id, 'status_focus' => $request->input('status')]);

        return response()->json(['status' => 'ok']);
    }
}
