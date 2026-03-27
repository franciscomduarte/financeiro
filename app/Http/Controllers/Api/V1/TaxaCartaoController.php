<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTaxaCartaoRequest;
use App\Http\Resources\TaxaCartaoResource;
use App\Models\TaxaCartao;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaxaCartaoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $taxas = TaxaCartao::select(['id', 'modalidade', 'percentual', 'ativo', 'updated_at'])
            ->orderBy('modalidade')
            ->get();

        return TaxaCartaoResource::collection($taxas);
    }

    public function update(UpdateTaxaCartaoRequest $request, TaxaCartao $taxaCartao): TaxaCartaoResource
    {
        $taxaCartao->update($request->validated());

        return new TaxaCartaoResource($taxaCartao);
    }
}
