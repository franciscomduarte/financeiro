<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\ClinicaAtual;
use App\Models\Paciente;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadFotoPacienteAction
{
    public function execute(Paciente $paciente, UploadedFile $file): Paciente
    {
        if ($paciente->foto_path) {
            Storage::disk('public')->delete($paciente->foto_path);
        }

        $extension = $file->getClientOriginalExtension();

        $path = $file->storeAs(
            app(ClinicaAtual::class)->pasta("pacientes/fotos/{$paciente->id}"),
            "avatar.{$extension}",
            'public'
        );

        $paciente->update(['foto_path' => $path]);

        return $paciente->fresh();
    }
}
