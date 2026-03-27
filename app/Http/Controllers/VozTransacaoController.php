<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TransacaoExtracaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class VozTransacaoController extends Controller
{
    public function processar(Request $request, TransacaoExtracaoService $extracao): JsonResponse
    {
        $request->validate([
            'texto' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            return response()->json($extracao->extrair($request->string('texto')->toString()));
        } catch (Throwable $e) {
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
