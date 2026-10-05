<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Prontuario\AdicionarFotosProntuarioAction;
use App\Models\ProntuarioOrientacao;
use App\Models\ProntuarioTermo;
use App\Support\GeradorPdf;
use Illuminate\Support\Facades\Storage;

/** PDFs do prontuário: termo assinado (com a assinatura e o código de verificação) e orientações. */
class PdfProntuarioService
{
    public function __construct(private readonly GeradorPdf $pdf) {}

    public function termo(ProntuarioTermo $termo): string
    {
        $termo->loadMissing(['paciente:id,tenant_id,nome,cpf', 'paciente.clinica', 'autor:id,name']);

        $png = Storage::disk(AdicionarFotosProntuarioAction::DISCO)->get($termo->assinatura_path);

        return $this->pdf->gerar('pdf.prontuario-termo', [
            'termo'      => $termo,
            'clinica'    => $termo->paciente->clinica,
            'assinatura' => $png !== null ? 'data:image/png;base64,' . base64_encode($png) : null,
        ]);
    }

    public function orientacao(ProntuarioOrientacao $orientacao): string
    {
        $orientacao->loadMissing(['paciente:id,tenant_id,nome', 'paciente.clinica', 'autor:id,name']);

        return $this->pdf->gerar('pdf.prontuario-orientacao', [
            'orientacao' => $orientacao,
            'clinica'    => $orientacao->paciente->clinica,
        ]);
    }
}
