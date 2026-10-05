<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Support\GeradorPdf;
use Illuminate\Http\Response;

/** PDF do orçamento para imprimir ou mandar ao paciente. */
class OrcamentoPdfController extends Controller
{
    public function __invoke(string $id, GeradorPdf $pdf): Response
    {
        $orcamento = Orcamento::query()->with(['paciente:id,tenant_id,nome', 'paciente.clinica', 'itens'])->findOrFail($id);

        return response($pdf->gerar('pdf.orcamento', ['orcamento' => $orcamento, 'clinica' => $orcamento->paciente->clinica]), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="orcamento-' . $orcamento->numero . '.pdf"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
