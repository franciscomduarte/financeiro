<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ExportarDadosPacienteAction;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/** LGPD: arquivo com todos os dados do paciente (só administradores). */
class PacienteExportacaoController extends Controller
{
    public function __invoke(string $id, ExportarDadosPacienteAction $exportar): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Só administradores exportam dados de pacientes.');

        $paciente = Paciente::findOrFail($id);
        $arquivo  = 'dados-' . Str::slug($paciente->nome) . '-' . now()->format('Y-m-d') . '.json';

        return response()->json($exportar->execute($paciente), 200, [
            'Content-Disposition' => 'attachment; filename="' . $arquivo . '"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
