<?php

declare(strict_types=1);

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;

/** Converte uma view HTML em PDF (A4), sem buscar arquivos externos. */
class GeradorPdf
{
    /** @param  array<string, mixed>  $dados */
    public function gerar(string $view, array $dados): string
    {
        $opcoes = new Options();
        $opcoes->set('isRemoteEnabled', false);
        $opcoes->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opcoes);
        $pdf->loadHtml(view($view, $dados)->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return (string) $pdf->output();
    }
}
