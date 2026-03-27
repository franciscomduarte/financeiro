<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateTransacaoAction;
use App\Services\TransacaoExtracaoService;
use App\Services\WhisperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessarAudioWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly string $audioUrl,
        private readonly string $remoteJid,
    ) {}

    public function handle(
        WhisperService           $whisper,
        TransacaoExtracaoService $extracao,
        CreateTransacaoAction    $criar,
    ): void {
        $tempPath = null;

        try {
            // 1. Download do áudio
            $response = Http::withOptions(['verify' => ! app()->isLocal()])
                ->timeout(30)
                ->get($this->audioUrl);

            if ($response->failed()) {
                throw new \RuntimeException("Falha ao baixar áudio (HTTP {$response->status()}).");
            }

            $tempPath = 'temp/wpp_' . uniqid('', true) . '.ogg';
            Storage::put($tempPath, $response->body());

            // 2. Transcrição via Whisper
            $texto = $whisper->transcrever(Storage::path($tempPath));

            Log::info('WhatsApp áudio transcrito', [
                'jid'   => $this->remoteJid,
                'texto' => $texto,
            ]);

            // 3. Extração de dados financeiros via LLM
            $dados = $extracao->extrair($texto);

            if (! $dados['valor_bruto']) {
                Log::warning('WhatsApp: valor não identificado no áudio, transação não salva.', [
                    'jid'   => $this->remoteJid,
                    'dados' => $dados,
                ]);
                return;
            }

            // 4. Persistência
            $transacao = $criar->execute($dados);

            Log::info('WhatsApp: transação criada via áudio', [
                'jid'          => $this->remoteJid,
                'transacao_id' => $transacao->id,
                'descricao'    => $transacao->descricao,
                'valor'        => $transacao->valor_bruto,
            ]);

        } catch (Throwable $e) {
            Log::error('WhatsApp: falha ao processar áudio', [
                'jid'   => $this->remoteJid,
                'url'   => $this->audioUrl,
                'erro'  => $e->getMessage(),
            ]);
            throw $e; // re-lança para o queue driver tentar novamente (respeita $tries)
        } finally {
            // 5. Limpeza do arquivo temporário sempre executada
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
        }
    }
}
