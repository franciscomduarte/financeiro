<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Models\ProntuarioFoto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Remove uma foto enviada por engano (só administradores). Fica registrado no log. */
class RemoverFotoProntuarioAction
{
    public function execute(ProntuarioFoto $foto): void
    {
        if (! auth()->user()?->isAdmin()) {
            throw new RuntimeException('Só administradores removem fotos do prontuário.');
        }

        $arquivos = array_filter([$foto->arquivo_path, $foto->miniatura_path]);
        $foto->delete();
        Storage::disk(AdicionarFotosProntuarioAction::DISCO)->delete($arquivos);

        Log::warning('[Prontuário] foto removida', [
            'foto_id'     => $foto->id,
            'paciente_id' => $foto->paciente_id,
            'tenant_id'   => $foto->tenant_id,
            'user_id'     => auth()->id(),
        ]);
    }
}
