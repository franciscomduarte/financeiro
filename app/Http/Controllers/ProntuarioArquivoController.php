<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Prontuario\AdicionarFotosProntuarioAction;
use App\Models\ProntuarioFoto;
use App\Models\ProntuarioOrientacao;
use App\Models\ProntuarioTermo;
use App\Services\PdfProntuarioService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Arquivos do prontuário (fotos e PDFs). Ficam no disco privado e só saem por aqui:
 * a busca pelo Model já aplica a clínica ativa e o escopo do profissional.
 */
class ProntuarioArquivoController extends Controller
{
    public function foto(string $id, ?string $tamanho = null): StreamedResponse
    {
        $foto = ProntuarioFoto::query()->select(['id', 'tenant_id', 'paciente_id', 'arquivo_path', 'miniatura_path', 'mime'])->findOrFail($id);
        $path = $tamanho === 'mini' && $foto->miniatura_path ? $foto->miniatura_path : $foto->arquivo_path;

        abort_unless(Storage::disk(AdicionarFotosProntuarioAction::DISCO)->exists($path), 404, 'Foto não encontrada.');

        return Storage::disk(AdicionarFotosProntuarioAction::DISCO)->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function termo(string $id, PdfProntuarioService $pdf): Response
    {
        $termo = ProntuarioTermo::findOrFail($id);

        return $this->pdf($pdf->termo($termo), 'termo-' . Str::slug($termo->titulo) . '-' . $termo->assinado_em->format('Y-m-d'));
    }

    public function orientacao(string $id, PdfProntuarioService $pdf): Response
    {
        $orientacao = ProntuarioOrientacao::findOrFail($id);

        return $this->pdf($pdf->orientacao($orientacao), 'orientacoes-' . Str::slug($orientacao->titulo));
    }

    private function pdf(string $conteudo, string $nome): Response
    {
        return response($conteudo, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nome . '.pdf"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
