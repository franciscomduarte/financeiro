<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Documento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateDocumentoAction
{
    public function execute(Documento $documento, array $data, ?UploadedFile $arquivo = null): Documento
    {
        if ($arquivo !== null) {
            $nome      = $arquivo->getClientOriginalName();
            $tamanhoKb = (int) ceil($arquivo->getSize() / 1024);
            if ($documento->arquivo_path) {
                Storage::disk('local')->delete($documento->arquivo_path);
            }
            $path = $arquivo->store('documentos', 'local');
            $data['arquivo_path']       = $path;
            $data['arquivo_nome']       = $nome;
            $data['arquivo_tamanho_kb'] = $tamanhoKb;
        }

        $documento->update($data);

        return $documento->fresh();
    }
}
