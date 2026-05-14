<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\DocumentoVersao;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoDownloadController extends Controller
{
    public function download(string $id): StreamedResponse|Response
    {
        $doc = Documento::findOrFail($id);

        abort_unless($doc->arquivo_path && Storage::disk('local')->exists($doc->arquivo_path), 404, 'Arquivo não encontrado.');

        return Storage::disk('local')->download($doc->arquivo_path, $doc->arquivo_nome ?? 'documento');
    }

    public function downloadVersao(string $id): StreamedResponse|Response
    {
        $versao = DocumentoVersao::findOrFail($id);

        abort_unless($versao->arquivo_path && Storage::disk('local')->exists($versao->arquivo_path), 404, 'Arquivo não encontrado.');

        return Storage::disk('local')->download($versao->arquivo_path, $versao->arquivo_nome ?? 'documento');
    }
}
