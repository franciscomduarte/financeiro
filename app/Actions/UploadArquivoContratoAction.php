<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\ClinicaAtual;
use App\Models\Contrato;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadArquivoContratoAction
{
    public function execute(Contrato $contrato, UploadedFile $file): Contrato
    {
        // Remove arquivo anterior se existir
        if ($contrato->arquivo_contrato_path) {
            Storage::delete($contrato->arquivo_contrato_path);
        }

        $path = $file->store(app(ClinicaAtual::class)->pasta("contratos/{$contrato->id}"), 'local');

        $contrato->update([
            'arquivo_contrato_path' => $path,
            'arquivo_contrato_nome' => $file->getClientOriginalName(),
        ]);

        return $contrato->fresh();
    }
}
