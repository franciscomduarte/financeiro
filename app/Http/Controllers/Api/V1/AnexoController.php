<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\UploadAnexoAction;
use App\Enums\TipoAnexo;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnexoRequest;
use App\Http\Resources\AnexoResource;
use App\Models\Transacao;
use App\Models\TransacaoAnexo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnexoController extends Controller
{
    public function store(StoreAnexoRequest $request, Transacao $transacao, UploadAnexoAction $action): JsonResponse
    {
        $result = $action->execute(
            $transacao,
            $request->file('arquivo'),
            TipoAnexo::from($request->validated('tipo')),
        );

        return response()->json([
            'data'              => new AnexoResource($result['anexo']),
            'suggest_mark_paid' => $result['suggest_mark_paid'],
        ], 201);
    }

    public function index(Transacao $transacao): AnonymousResourceCollection
    {
        $anexos = $transacao->anexos()
            ->select(['id', 'transacao_id', 'tipo', 'nome_arquivo', 'mime_type', 'tamanho_bytes', 'created_at'])
            ->get();

        return AnexoResource::collection($anexos);
    }

    public function destroy(Transacao $transacao, TransacaoAnexo $anexo): JsonResponse
    {
        abort_unless($anexo->transacao_id === $transacao->id, 404);

        Storage::disk('local')->delete($anexo->caminho);
        $anexo->delete();

        return response()->json(['message' => 'Anexo removido com sucesso.']);
    }

    /** PDF e imagem abrem no navegador ("Abrir arquivo"); o resto é baixado. */
    private const ABRE_NO_NAVEGADOR = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function download(TransacaoAnexo $anexo): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($anexo->caminho), 404, 'Arquivo não encontrado.');

        $tipo = (string) ($anexo->mime_type ?? 'application/octet-stream');

        return Storage::disk('local')->response(
            $anexo->caminho,
            $anexo->nome_arquivo,
            ['Content-Type' => $tipo, 'X-Content-Type-Options' => 'nosniff'],
            in_array($tipo, self::ABRE_NO_NAVEGADOR, true) ? 'inline' : 'attachment',
        );
    }
}
