<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\ClinicaAtual;
use App\Enums\StatusDocumento;
use App\Models\Documento;
use App\Models\DocumentoVersao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RenovarDocumentoAction
{
    public function execute(Documento $documento, array $data, ?UploadedFile $arquivo = null): Documento
    {
        return DB::transaction(function () use ($documento, $data, $arquivo): Documento {
            // Arquiva a versão atual antes de renovar
            DocumentoVersao::create([
                'documento_id'    => $documento->id,
                'numero_documento' => $documento->numero_documento,
                'data_emissao'    => $documento->data_emissao,
                'data_validade'   => $documento->data_validade,
                'arquivo_path'    => $documento->arquivo_path,
                'arquivo_nome'    => $documento->arquivo_nome,
                'observacoes'     => $documento->observacoes,
            ]);

            $update = [
                'numero_documento' => $data['numero_documento'] ?? $documento->numero_documento,
                'data_emissao'     => $data['data_emissao'] ?? null,
                'data_validade'    => $data['data_validade'] ?? null,
                'status'           => StatusDocumento::Vigente->value,
                'observacoes'      => $data['observacoes'] ?? $documento->observacoes,
            ];

            if ($arquivo !== null) {
                $nome      = $arquivo->getClientOriginalName();
                $tamanhoKb = (int) ceil($arquivo->getSize() / 1024);
                $path      = $arquivo->store(app(ClinicaAtual::class)->pasta('documentos'), 'local');
                $update['arquivo_path']       = $path;
                $update['arquivo_nome']       = $nome;
                $update['arquivo_tamanho_kb'] = $tamanhoKb;
            }

            $documento->update($update);

            return $documento->fresh();
        });
    }
}
