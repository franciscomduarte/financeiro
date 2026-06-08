<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBloqueioAgendaRequest;
use App\Models\BloqueioAgenda;
use App\Models\Profissional;
use Illuminate\Http\JsonResponse;

class BloqueioAgendaController extends Controller
{
    public function index(Profissional $profissional): JsonResponse
    {
        $bloqueios = $profissional->bloqueiosAgenda()
            ->where('fim_em', '>=', now())
            ->orderBy('inicio_em')
            ->get(['id', 'inicio_em', 'fim_em', 'motivo']);

        return response()->json(['data' => $bloqueios]);
    }

    public function store(StoreBloqueioAgendaRequest $request, Profissional $profissional): JsonResponse
    {
        $bloqueio = $profissional->bloqueiosAgenda()->create($request->validated());

        return response()->json(['data' => $bloqueio], 201);
    }

    public function destroy(BloqueioAgenda $bloqueio): JsonResponse
    {
        $bloqueio->delete();

        return response()->json(null, 204);
    }
}
