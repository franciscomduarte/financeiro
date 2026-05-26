<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateContratoAction;
use App\Actions\RegistrarReajusteAction;
use App\Actions\UpdateContratoAction;
use App\Actions\UploadArquivoContratoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReajusteContratoRequest;
use App\Http\Requests\Api\StoreContratoRequest;
use App\Http\Requests\Api\UpdateContratoRequest;
use App\Http\Resources\ContratoReajusteResource;
use App\Http\Resources\ContratoResource;
use App\Models\Contrato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContratoController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $query = Contrato::query()
            ->with('fornecedor')
            ->orderBy('status')
            ->orderBy('data_fim');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('risco')) {
            $query->where('risco', $request->input('risco'));
        }

        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->input('fornecedor_id'));
        }

        if ($request->boolean('alertas')) {
            $query->where(function ($q): void {
                $q->whereNotNull('data_fim')
                  ->where('data_fim', '<=', now()->addDays(60)->toDateString())
                  ->orWhere(function ($q2): void {
                      $q2->whereNotNull('data_proximo_reajuste')
                         ->where('data_proximo_reajuste', '<=', now()->addDays(30)->toDateString());
                  });
            });
        }

        return ContratoResource::collection($query->paginate(20));
    }

    public function store(StoreContratoRequest $request, CreateContratoAction $action): ContratoResource
    {
        $contrato = $action->execute($request->validated());
        $contrato->load('fornecedor');

        return new ContratoResource($contrato);
    }

    public function show(Contrato $contrato): ContratoResource
    {
        $contrato->load(['fornecedor', 'reajustes']);

        return new ContratoResource($contrato);
    }

    public function update(UpdateContratoRequest $request, Contrato $contrato, UpdateContratoAction $action): ContratoResource
    {
        $contrato = $action->execute($contrato, $request->validated());
        $contrato->load('fornecedor');

        return new ContratoResource($contrato);
    }

    public function reajuste(ReajusteContratoRequest $request, Contrato $contrato, RegistrarReajusteAction $action): ContratoReajusteResource
    {
        $reajuste = $action->execute($contrato, $request->validated());

        return new ContratoReajusteResource($reajuste);
    }

    public function uploadArquivo(Request $request, Contrato $contrato, UploadArquivoContratoAction $action): ContratoResource
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'max:102400', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $contrato = $action->execute($contrato, $request->file('arquivo'));

        return new ContratoResource($contrato);
    }

    public function downloadArquivo(Contrato $contrato): StreamedResponse
    {
        abort_unless($contrato->temArquivo(), 404, 'Contrato sem arquivo.');

        return Storage::download(
            $contrato->arquivo_contrato_path,
            $contrato->arquivo_contrato_nome
        );
    }
}
