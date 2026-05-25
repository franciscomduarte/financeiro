<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ObrigacaoFiscalLancamento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadGuiaFiscalAction
{
    public function execute(ObrigacaoFiscalLancamento $lancamento, UploadedFile $file): ObrigacaoFiscalLancamento
    {
        if ($lancamento->arquivo_path) {
            Storage::disk('local')->delete($lancamento->arquivo_path);
        }

        $path = $file->store("guias-fiscais/{$lancamento->id}", 'local');

        $lancamento->update([
            'arquivo_path' => $path,
            'arquivo_nome' => $file->getClientOriginalName(),
        ]);

        return $lancamento->fresh();
    }
}
