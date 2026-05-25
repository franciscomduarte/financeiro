<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContaConsumoFatura;
use App\Models\Contrato;
use App\Models\ObrigacaoFiscalLancamento;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArquivoDownloadController extends Controller
{
    public function downloadFatura(string $id): StreamedResponse|Response
    {
        $fatura = ContaConsumoFatura::findOrFail($id);

        abort_unless(
            $fatura->arquivo_path && Storage::disk('local')->exists($fatura->arquivo_path),
            404,
            'Arquivo não encontrado.'
        );

        return Storage::disk('local')->download($fatura->arquivo_path, $fatura->arquivo_nome ?? 'fatura');
    }

    public function downloadGuiaFiscal(string $id): StreamedResponse|Response
    {
        $lancamento = ObrigacaoFiscalLancamento::findOrFail($id);

        abort_unless(
            $lancamento->arquivo_path && Storage::disk('local')->exists($lancamento->arquivo_path),
            404,
            'Arquivo não encontrado.'
        );

        return Storage::disk('local')->download($lancamento->arquivo_path, $lancamento->arquivo_nome ?? 'guia-fiscal');
    }

    public function downloadContrato(string $id): StreamedResponse|Response
    {
        $contrato = Contrato::findOrFail($id);

        abort_unless(
            $contrato->arquivo_contrato_path && Storage::disk('local')->exists($contrato->arquivo_contrato_path),
            404,
            'Arquivo não encontrado.'
        );

        return Storage::disk('local')->download($contrato->arquivo_contrato_path, $contrato->arquivo_contrato_nome ?? 'contrato');
    }
}
