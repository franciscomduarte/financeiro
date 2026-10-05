<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StatusClinica;
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
