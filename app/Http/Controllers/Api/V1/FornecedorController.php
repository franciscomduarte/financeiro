<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateFornecedorAction;
use App\Actions\UpdateFornecedorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFornecedorRequest;
use App\Http\Requests\Api\UpdateFornecedorRequest;
use App\Http\Resources\FornecedorResource;
use App\Models\Fornecedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FornecedorController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $query = Fornecedor::query()
            ->withCount('contratos')
            ->orderBy('nome_fantasia');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->input('categoria'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term): void {
                $q->where('nome_fantasia', 'ilike', "%{$term}%")
                  ->orWhere('servico_prestado', 'ilike', "%{$term}%")
                  ->orWhere('cnpj', 'like', "%{$term}%");
            });
        }

        return FornecedorResource::collection($query->paginate(20));
    }

    public function store(StoreFornecedorRequest $request, CreateFornecedorAction $action): FornecedorResource
    {
        $fornecedor = $action->execute($request->validated());

        return new FornecedorResource($fornecedor);
    }

    public function show(Fornecedor $fornecedor): FornecedorResource
    {
        $fornecedor->loadCount('contratos');

        return new FornecedorResource($fornecedor);
    }

    public function update(UpdateFornecedorRequest $request, Fornecedor $fornecedor, UpdateFornecedorAction $action): FornecedorResource
    {
        $fornecedor = $action->execute($fornecedor, $request->validated());

        return new FornecedorResource($fornecedor);
    }
}
