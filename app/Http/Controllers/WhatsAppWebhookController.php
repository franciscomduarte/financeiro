<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Notificacoes\AtualizarEntregaNotificacaoAction;
use App\Enums\StatusClinica;
use App\Enums\StatusNotificacao;
use App\Models\Clinica;
use App\Support\ClinicaAtual;
use App\Jobs\ProcessarAudioWhatsAppJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Recebe o webhook da Evolution API.
     * Sempre retorna 200 para evitar retentativas do bot.
     */
    public function handle(Request $request): JsonResponse
    {
        // Validação do token do webhook (se configurado)
        if (! $this->tokenValido($request)) {
            Log::warning('WhatsApp webhook: token inválido', ['ip' => $request->ip()]);
            return response()->json(['status' => 'unauthorized']);
        }

        // Retorno de entrega/leitura das mensagens enviadas pela clínica
        if (str_replace('_', '.', mb_strtolower((string) $request->input('event'))) === 'messages.update') {
            return response()->json(['status' => 'ok', 'atualizadas' => $this->atualizarEntregas($request)]);
        }

        // Ignora eventos que não são mensagens de áudio
        if ($request->input('data.messageType') !== 'audioMessage') {
            return response()->json(['status' => 'ignored']);
        }

        $remoteJid = (string) $request->input('data.key.remoteJid', '');

        // Ignora mensagens enviadas pelo próprio bot (fromMe)
        if ($request->boolean('data.key.fromMe')) {
            return response()->json(['status' => 'ignored_own_message']);
        }

        // O remetente precisa ser o número autorizado de alguma clínica; o lançamento vai para ela
        $clinica = $this->clinicaDoRemetente($remoteJid);
        if ($clinica === null) {
            Log::info('WhatsApp webhook: remetente não autorizado', ['jid' => $remoteJid]);
            return response()->json(['status' => 'unauthorized']);
        }

        $audioUrl = (string) $request->input('data.message.audioMessage.url', '');

        if (blank($audioUrl)) {
            Log::warning('WhatsApp webhook: URL do áudio ausente', ['payload' => $request->all()]);
            return response()->json(['status' => 'missing_audio_url']);
        }

        // Despacha processamento para a fila (resposta imediata ao webhook); o job herda a clínica
        app(ClinicaAtual::class)->executarComo($clinica, function () use ($audioUrl, $remoteJid): void {
            ProcessarAudioWhatsAppJob::dispatch($audioUrl, $remoteJid);
        });

        Log::info('WhatsApp webhook: áudio enfileirado', ['jid' => $remoteJid, 'tenant_id' => $clinica->id]);

        return response()->json(['status' => 'queued']);
    }

    /** Evolution v1/v2: "data" é um objeto ou uma lista; status em texto (DELIVERY_ACK, READ) ou número. */
    private function atualizarEntregas(Request $request): int
    {
        $data  = $request->input('data', []);
        $itens = is_array($data) && array_is_list($data) ? $data : [$data];
        $acao  = app(AtualizarEntregaNotificacaoAction::class);
        $total = 0;

        foreach ($itens as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id     = $item['keyId'] ?? $item['key']['id'] ?? $item['id'] ?? null;
            $bruto  = $item['status'] ?? $item['update']['status'] ?? null;
            $status = match (true) {
                in_array($bruto, ['DELIVERY_ACK', 3, '3'], true)          => StatusNotificacao::Entregue,
                in_array($bruto, ['READ', 'PLAYED', 4, 5, '4', '5'], true) => StatusNotificacao::Lida,
                in_array($bruto, ['ERROR', 0, '0'], true)                 => StatusNotificacao::Falhou,
                default                                                   => null,
            };

            if (is_string($id) && $id !== '' && $status !== null && $acao->porIdExterno($id, $status, 'O WhatsApp informou erro na entrega.')) {
                $total++;
            }
        }

        return $total;
    }

    private function tokenValido(Request $request): bool
    {
        $tokenEsperado = config('services.whatsapp.webhook_token');

        // Se nenhum token configurado, qualquer requisição é aceita
        if (blank($tokenEsperado)) {
            return true;
        }

        // Evolution API envia o token no header "apikey"
        return $request->header('apikey') === $tokenEsperado;
    }

    /** Clínica cujo número autorizado (clinicas.whatsapp_numero) enviou a mensagem. */
    private function clinicaDoRemetente(string $remoteJid): ?Clinica
    {
        $remetente = preg_replace('/\D/', '', $remoteJid);
        if ($remetente === '') {
            return null;
        }

        return Clinica::query()
            ->whereNotNull('whatsapp_numero')
            ->where('status', '!=', StatusClinica::Bloqueada->value)
            ->limit(500)
            ->get(['id', 'nome', 'status', 'whatsapp_numero'])
            ->first(function (Clinica $c) use ($remetente): bool {
                $autorizado = preg_replace('/\D/', '', (string) $c->whatsapp_numero);

                return $autorizado !== '' && str_contains($remetente, $autorizado);
            });
    }
}
