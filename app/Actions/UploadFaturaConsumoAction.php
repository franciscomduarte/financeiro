<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\ClinicaAtual;
use App\Models\ContaConsumoFatura;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadFaturaConsumoAction
{
    public function execute(ContaConsumoFatura $fatura, UploadedFile $file): ContaConsumoFatura
    {
        if ($fatura->arquivo_path) {
            Storage::disk('local')->delete($fatura->arquivo_path);
        }

        $path = $file->store(app(ClinicaAtual::class)->pasta("faturas/{$fatura->id}"), 'local');

        $fatura->update([
            'arquivo_path' => $path,
            'arquivo_nome' => $file->getClientOriginalName(),
        ]);

        return $fatura->fresh();
    }
}
