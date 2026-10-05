<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Prontuario\AdicionarFotosProntuarioAction;
use App\Models\ProntuarioOrientacao;
use App\Models\ProntuarioTermo;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

/** PDFs do prontuário: termo assinado (com a assinatura e o código de verificação) e orientações. */
class PdfProntuarioService
{
    public function termo(ProntuarioTermo $termo): string
    {
        $termo->loadMissing(['paciente:id,tenant_id,nome,cpf', 'paciente.clinica', 'autor:id,name']);

        $png = Storage::disk(AdicionarFotosProntuarioAction::DISCO)->get($termo->assinatura_path);

        return $this->renderizar(view('pdf.prontuario-termo', [
            'termo'      => $termo,
            'clinica'    => $termo->paciente->clinica,
            'assinatura' => $png !== null ? 'data:image/png;base64,' . base64_encode($png) : null,
        ])->render());
    }

    public function orientacao(ProntuarioOrientacao $orientacao): string
    {
        $orientacao->loadMissing(['paciente:id,tenant_id,nome', 'paciente.clinica', 'autor:id,name']);

        return $this->renderizar(view('pdf.prontuario-orientacao', [
            'orientacao' => $orientacao,
            'clinica'    => $orientacao->paciente->clinica,
        ])->render());
    }

    private function renderizar(string $html): string
    {
        $opcoes = new Options();
        $opcoes->set('isRemoteEnabled', false); // nada de buscar arquivos externos
        $opcoes->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opcoes);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return (string) $pdf->output();
    }
}
