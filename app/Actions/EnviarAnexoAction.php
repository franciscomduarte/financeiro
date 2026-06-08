<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\AnexoTransacaoMail;
use App\Models\Paciente;
use App\Models\TransacaoAnexo;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class EnviarAnexoAction
{
    public function __construct(
        private readonly WhatsAppService $whatsapp,
    ) {}

    /**
     * @return array{email: bool|null, whatsapp: bool|null}
     */
    public function execute(
        TransacaoAnexo $anexo,
        Paciente $paciente,
        bool $viaEmail,
        bool $viaWhatsapp,
    ): array {
        if (! Storage::disk('local')->exists($anexo->caminho)) {
            throw new RuntimeException("Arquivo não encontrado no servidor: {$anexo->nome_arquivo}");
        }

        $resultado = ['email' => null, 'whatsapp' => null];

        if ($viaEmail && $paciente->email) {
            try {
                Mail::to($paciente->email)->send(new AnexoTransacaoMail($paciente, $anexo));
                $resultado['email'] = true;
                Log::info("EnviarAnexo: email enviado", ['anexo' => $anexo->id, 'paciente' => $paciente->id]);
            } catch (Throwable $e) {
                Log::error("EnviarAnexo: falha no email", ['anexo' => $anexo->id, 'error' => $e->getMessage()]);
                $resultado['email'] = false;
            }
        }

        if ($viaWhatsapp && $paciente->telefone) {
            try {
                $conteudo = (string) Storage::disk('local')->get($anexo->caminho);
                $base64   = base64_encode($conteudo);
                $caption  = '📎 ' . ucfirst($anexo->tipo->value) . ' — LC Estética';

                $ok = $this->whatsapp->enviarDocumento(
                    numero:      $paciente->telefone,
                    base64:      $base64,
                    mimeType:    $anexo->mime_type,
                    nomeArquivo: $anexo->nome_arquivo,
                    caption:     $caption,
                );

                $resultado['whatsapp'] = $ok;
                if (! $ok) {
                    Log::warning("EnviarAnexo: WhatsApp retornou erro", ['anexo' => $anexo->id]);
                }
            } catch (Throwable $e) {
                Log::error("EnviarAnexo: falha no WhatsApp", ['anexo' => $anexo->id, 'error' => $e->getMessage()]);
                $resultado['whatsapp'] = false;
            }
        }

        return $resultado;
    }
}
