<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateTransacaoAction;
use App\Actions\UpdateTransacaoAction;
use App\Enums\StatusTransacao;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransacaoRequest;
use App\Http\Requests\UpdateTransacaoRequest;
use App\Http\Resources\TransacaoResource;
use App\Models\Transacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransacaoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $transacoes = Transacao::query()
            ->select(['id', 'tipo', 'fase', 'categoria', 'subcategoria', 'centro_custo', 'descricao', 'cliente', 'valor_bruto', 'taxa_operacional', 'imposto_estimado', 'valor_liquido', 'data_competencia', 'data_pagamento', 'forma_pagamento', 'num_parcelas', 'parcela_atual', 'status', 'recorrencia', 'transacao_pai_id', 'created_at', 'updated_at'])
            ->when($request->input('tipo'), fn ($q, $v) => $q->where('tipo', $v))
            ->when($request->input('fase'), fn ($q, $v) => $q->where('fase', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('categoria'), fn ($q, $v) => $q->where('categoria', $v))
            ->when($request->input('periodo.inicio'), fn ($q, $v) => $q->whereDate('data_competencia', '>=', $v))
            ->when($request->input('periodo.fim'), fn ($q, $v) => $q->whereDate('data_competencia', '<=', $v))
            ->orderByDesc('data_competencia')
            ->paginate(20);

        return TransacaoResource::collection($transacoes);
    }

    public function store(StoreTransacaoRequest $request, CreateTransacaoAction $action): TransacaoResource
    {
        $transacao = $action->execute($request->validated());

        return new TransacaoResource($transacao);
    }

    public function show(Transacao $transacao): TransacaoResource
    {
        $transacao->load('anexos');

        return new TransacaoResource($transacao);
    }

    public function update(UpdateTransacaoRequest $request, Transacao $transacao, UpdateTransacaoAction $action): TransacaoResource
    {
        $transacao = $action->execute($transacao, $request->validated());

        return new TransacaoResource($transacao);
    }

    public function destroy(Transacao $transacao): JsonResponse
    {
        $transacao->update(['status' => StatusTransacao::Cancelado]);

        return response()->json(['message' => 'Transação cancelada com sucesso.']);
    }
}
