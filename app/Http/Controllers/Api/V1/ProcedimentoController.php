<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcedimentoRequest;
use App\Http\Resources\ProcedimentoResource;
use App\Models\Procedimento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProcedimentoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $procedimentos = Procedimento::query()
            ->when($request->boolean('ativo', true), fn ($q) => $q->where('ativo', true))
            ->orderBy('nome')
            ->paginate(50);

        return ProcedimentoResource::collection($procedimentos);
    }

    public function store(StoreProcedimentoRequest $request): ProcedimentoResource
    {
        $procedimento = Procedimento::create($request->validated());

        return new ProcedimentoResource($procedimento);
    }

    public function show(Procedimento $procedimento): ProcedimentoResource
    {
        return new ProcedimentoResource($procedimento);
    }

    public function update(StoreProcedimentoRequest $request, Procedimento $procedimento): ProcedimentoResource
    {
        $procedimento->update($request->validated());

        return new ProcedimentoResource($procedimento);
    }

    public function destroy(Procedimento $procedimento): JsonResponse
    {
        $procedimento->update(['ativo' => false]);

        return response()->json(null, 204);
    }
}
