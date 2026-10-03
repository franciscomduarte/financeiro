<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProfissionalRequest;
use App\Http\Requests\UpdateProfissionalRequest;
use App\Http\Resources\ProfissionalResource;
use App\Models\GradeHorario;
use App\Models\Profissional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProfissionalController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProfissionalResource::collection(
            Profissional::orderBy('nome')->paginate(20)
        );
    }

    public function store(StoreProfissionalRequest $request): ProfissionalResource
    {
        $profissional = Profissional::create($request->validated());

        return new ProfissionalResource($profissional);
    }

    public function show(Profissional $profissional): ProfissionalResource
    {
        return new ProfissionalResource($profissional);
    }

    public function update(UpdateProfissionalRequest $request, Profissional $profissional): ProfissionalResource
    {
        $profissional->update($request->validated());

        return new ProfissionalResource($profissional);
    }

    public function destroy(Profissional $profissional): JsonResponse
    {
        $profissional->update(['ativo' => false]);

        return response()->json(null, 204);
    }

    public function grade(Profissional $profissional): JsonResponse
    {
        $grade = $profissional->gradeHorarios()
            ->orderBy('dia_semana')
            ->get(['id', 'dia_semana', 'hora_inicio', 'hora_fim', 'intervalo_inicio', 'intervalo_fim', 'ativo']);

        return response()->json(['data' => $grade]);
    }

    public function atualizarGrade(Request $request, Profissional $profissional): JsonResponse
    {
        $request->validate([
            'grade'                  => ['required', 'array'],
            'grade.*.dia_semana'     => ['required', 'integer', 'between:0,6'],
            'grade.*.hora_inicio'    => ['required', 'date_format:H:i'],
            'grade.*.hora_fim'       => ['required', 'date_format:H:i', 'after:grade.*.hora_inicio'],
            'grade.*.intervalo_inicio' => ['nullable', 'date_format:H:i', 'required_with:grade.*.intervalo_fim', 'after:grade.*.hora_inicio'],
            'grade.*.intervalo_fim'    => ['nullable', 'date_format:H:i', 'required_with:grade.*.intervalo_inicio', 'after:grade.*.intervalo_inicio', 'before:grade.*.hora_fim'],
            'grade.*.ativo'          => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $profissional): void {
            foreach ($request->input('grade') as $item) {
                GradeHorario::updateOrCreate(
                    ['profissional_id' => $profissional->id, 'dia_semana' => $item['dia_semana']],
                    [
                        'hora_inicio'      => $item['hora_inicio'],
                        'hora_fim'         => $item['hora_fim'],
                        'intervalo_inicio' => $item['intervalo_inicio'] ?? null,
                        'intervalo_fim'    => $item['intervalo_fim'] ?? null,
                        'ativo'            => $item['ativo'] ?? true,
                    ],
                );
            }
        });

        return response()->json(['message' => 'Grade atualizada.']);
    }
}
