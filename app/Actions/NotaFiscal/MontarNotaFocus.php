<?php

declare(strict_types=1);

namespace App\Actions\NotaFiscal;

use App\Models\Clinica;
use App\Models\NotaFiscal;

/** Monta o JSON da NFS-e no formato da Focus NFe (API v2). */
class MontarNotaFocus
{
    /** @return array<string, mixed> */
    public function montar(NotaFiscal $nota, Clinica $clinica): array
    {
        $tomador = ['razao_social' => $nota->tomador_nome];
        if ($nota->tomador_cpf !== null) {
            $tomador[strlen($nota->tomador_cpf) === 14 ? 'cnpj' : 'cpf'] = $nota->tomador_cpf;
        }
        if ($nota->tomador_email !== null) {
            $tomador['email'] = $nota->tomador_email; // a Focus manda a nota por e-mail ao tomador
        }

        return [
            'data_emissao'             => now(config('clinica.fuso_horario'))->toIso8601String(),
            'optante_simples_nacional' => (bool) $clinica->nfse_optante_simples,
            'prestador'                => [
                'cnpj'                => preg_replace('/\D/', '', (string) $clinica->cnpj),
                'inscricao_municipal' => preg_replace('/\D/', '', (string) $clinica->inscricao_municipal),
                'codigo_municipio'    => $clinica->codigo_municipio,
            ],
            'tomador'                  => $tomador,
            'servico'                  => array_filter([
                'valor_servicos'              => (float) $nota->valor,
                'discriminacao'               => $nota->discriminacao,
                'item_lista_servico'          => $clinica->nfse_item_lista_servico,
                'codigo_tributario_municipio' => $clinica->nfse_codigo_tributario,
                'aliquota'                    => (float) $clinica->nfse_aliquota_iss,
                'iss_retido'                  => false,
                'codigo_municipio'            => $clinica->codigo_municipio,
            ], fn ($v) => $v !== null && $v !== ''),
        ];
    }
}
