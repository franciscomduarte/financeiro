<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Notificacoes\AtualizarEntregaNotificacaoAction;
use App\Enums\StatusNotificacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook transacional da Brevo: entrega, abertura e recusa dos e-mails da central de notificações.
 * O token secreto vai na URL (configurada no painel da Brevo).
 */
class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request, string $token, AtualizarEntregaNotificacaoAction $acao): JsonResponse
    {
        $esperado = (string) config('services.brevo.webhook_token');
        if ($esperado === '' || ! hash_equals($esperado, $token)) {
            Log::warning('Brevo webhook: token inválido', ['ip' => $request->ip()]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $eventos = array_is_list($request->all()) ? $request->all() : [$request->all()];
        $total   = 0;

        foreach ($eventos as $e) {
            $custom = (string) ($e['X-Mailin-custom'] ?? $e['x-mailin-custom'] ?? '');
            if (! preg_match('/^notificacao:([0-9a-f-]{36})$/i', $custom, $m)) {
                continue;
            }

            $status = match ((string) ($e['event'] ?? '')) {
                'delivered'                                   => StatusNotificacao::Entregue,
                'opened', 'unique_opened', 'proxy_open', 'click' => StatusNotificacao::Lida,
                'hard_bounce', 'soft_bounce', 'blocked', 'invalid_email', 'error', 'spam' => StatusNotificacao::Falhou,
                default                                       => null,
            };

            if ($status !== null && $acao->porId($m[1], $status, 'E-mail recusado pelo destinatário (' . ($e['event'] ?? '') . ($e['reason'] ?? '' ? ': ' . $e['reason'] : '') . ').')) {
                $total++;
            }
        }

        return response()->json(['status' => 'ok', 'atualizadas' => $total]);
    }
}
