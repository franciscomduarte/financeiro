<?php

declare(strict_types=1);

namespace App\Actions\NotaFiscal;

use App\Enums\StatusNotaFiscal;
use App\Models\NotaFiscal;
use App\Services\FocusNfeService;
use Illuminate\Support\Facades\Log;

/** Consulta a nota na Focus NFe e grava a situação (número, links, erro). */
class AtualizarSituacaoNotaFiscalAction
{
    /** Consultas com "não encontrada" toleradas antes de dar a nota como perdida. */
    public const CONSULTAS_ATE_DESISTIR = 3;

    public function __construct(private readonly FocusNfeService $focus) {}

    public function execute(NotaFiscal $nota): NotaFiscal
    {
        $r = $this->focus->consultar($nota->padrao, $nota->referencia);

        $dados = ['consultas' => $nota->consultas + 1];

        match ($r['status']) {
            'autorizado' => $dados += [
                'status'             => StatusNotaFiscal::Autorizada,
                'numero'             => $r['numero'],
                'codigo_verificacao' => $r['codigo_verificacao'],
                'url'                => $r['url'],
                'url_xml'            => $r['url_xml'],
                'mensagem_erro'      => null,
                'autorizada_em'      => $nota->autorizada_em ?? now(),
            ],
            'cancelado' => $dados += ['status' => StatusNotaFiscal::Cancelada, 'cancelada_em' => $nota->cancelada_em ?? now()],
            'erro_autorizacao', 'erro' => $dados += [
                'status'        => StatusNotaFiscal::Erro,
                'mensagem_erro' => $r['mensagem'] ?? 'A prefeitura recusou a nota.',
            ],
            'nao_encontrado' => $dados['consultas'] >= self::CONSULTAS_ATE_DESISTIR ? $dados += [
                'status'        => StatusNotaFiscal::Erro,
                'mensagem_erro' => 'A Focus NFe não encontrou esta nota. Confira em Dados da clínica › Nota fiscal se o padrão '
                    . '(da prefeitura ou nacional) é o que a Focus usa no seu município e emita de novo.',
            ] : null,
            default => null, // processando_autorizacao: segue aguardando
        };

        $nota->update($dados);

        if ($nota->wasChanged('status')) {
            Log::info('[NFS-e] situação atualizada', ['nota_id' => $nota->id, 'status' => $nota->status->value]);
        }

        return $nota;
    }
}
