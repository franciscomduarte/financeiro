<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TipoAnexo;
use App\Models\Transacao;
use App\Models\TransacaoAnexo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadAnexoAction
{
    public function execute(Transacao $transacao, UploadedFile $arquivo, TipoAnexo $tipo): array
    {
        return DB::transaction(function () use ($transacao, $arquivo, $tipo): array {
            $diretorio = "anexos/transacoes/{$transacao->id}";
            $caminho   = Storage::disk('local')->putFile($diretorio, $arquivo);

            $anexo = TransacaoAnexo::create([
                'transacao_id'  => $transacao->id,
                'tipo'          => $tipo,
                'nome_arquivo'  => $arquivo->getClientOriginalName(),
                'caminho'       => $caminho,
                'mime_type'     => $arquivo->getMimeType(),
                'tamanho_bytes' => $arquivo->getSize(),
                'created_at'    => now(),
            ]);

            $suggestMarkPaid = $tipo === TipoAnexo::Comprovante
                && $transacao->status->value !== 'pago';

            return [
                'anexo'             => $anexo,
                'suggest_mark_paid' => $suggestMarkPaid,
            ];
        });
    }
}
