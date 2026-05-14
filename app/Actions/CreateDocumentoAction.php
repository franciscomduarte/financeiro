<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Documento;
use Illuminate\Http\UploadedFile;

class CreateDocumentoAction
{
    public function execute(array $data, ?UploadedFile $arquivo = null): Documento
    {
        if ($arquivo !== null) {
            $nome      = $arquivo->getClientOriginalName();
            $tamanhoKb = (int) ceil($arquivo->getSize() / 1024);
            $path      = $arquivo->store('documentos', 'local');
            $data['arquivo_path']       = $path;
            $data['arquivo_nome']       = $nome;
            $data['arquivo_tamanho_kb'] = $tamanhoKb;
        }

        return Documento::create($data);
    }
}
