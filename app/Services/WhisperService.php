<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhisperService
{
    public function transcrever(string $caminhoAbsoluto): string
    {
        if (! file_exists($caminhoAbsoluto)) {
            throw new RuntimeException("Arquivo de áudio não encontrado: {$caminhoAbsoluto}");
        }

        $response = Http::withOptions(['verify' => ! app()->isLocal()])
            ->withToken(config('services.openai.key'))
            ->attach(
                name:     'file',
                contents: file_get_contents($caminhoAbsoluto),
                filename: basename($caminhoAbsoluto),
            )
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model'    => 'whisper-1',
                'language' => 'pt',
            ]);

        if ($response->failed()) {
            Log::error('WhisperService: falha na transcrição', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('Falha ao transcrever o áudio: ' . $response->status());
        }

        $texto = $response->json('text', '');

        if (blank($texto)) {
            throw new RuntimeException('Whisper não retornou texto. O áudio pode estar vazio ou inaudível.');
        }

        return $texto;
    }
}
