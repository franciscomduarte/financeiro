<?php

declare(strict_types=1);

namespace App\Actions\NotaFiscal;

use App\Enums\PadraoNfse;
use App\Models\Clinica;
use App\Models\NotaFiscal;

/** Monta o JSON da NFS-e no formato da Focus NFe (API v2): padrão da prefeitura ou NFS-e Nacional. */
class MontarNotaFocus
{
    /** @return array<string, mixed> */
    public function montar(NotaFiscal $nota, Clinica $clinica): array
    {
        if ($nota->padrao === PadraoNfse::Nacional) {
            return $this->nacional($nota, $clinica);
        }

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

    /**
     * NFS-e Nacional (DPS): campos planos, com códigos do leiaute nacional.
     *
     * @return array<string, mixed>
     */
    private function nacional(NotaFiscal $nota, Clinica $clinica): array
    {
        $agora = now(config('clinica.fuso_horario'));
        $doc   = $nota->tomador_cpf !== null ? [(strlen($nota->tomador_cpf) === 14 ? 'cnpj_tomador' : 'cpf_tomador') => $nota->tomador_cpf] : [];

        return array_filter([
            'data_emissao'                   => $agora->toIso8601String(),
            'data_competencia'               => $agora->toDateString(),
            'codigo_municipio_emissora'      => $clinica->codigo_municipio,
            'cnpj_prestador'                 => preg_replace('/\D/', '', (string) $clinica->cnpj),
            'inscricao_municipal_prestador'  => preg_replace('/\D/', '', (string) $clinica->inscricao_municipal) ?: null,
            'codigo_opcao_simples_nacional'  => $clinica->nfse_optante_simples ? 3 : 1, // 1 não optante · 3 ME/EPP
            'regime_especial_tributacao'     => 0,                                      // nenhum
            ...$doc,
            'razao_social_tomador'           => $nota->tomador_nome,
            'email_tomador'                  => $nota->tomador_email,
            'codigo_municipio_prestacao'     => $clinica->codigo_municipio,
            'codigo_tributacao_nacional_iss' => $clinica->nfse_codigo_tributacao_nacional,
            'codigo_tributacao_municipal_iss' => $clinica->nfse_codigo_tributario,
            'descricao_servico'              => $nota->discriminacao,
            'valor_servico'                  => (float) $nota->valor,
            'tributacao_iss'                 => 1, // operação tributável
            'tipo_retencao_iss'              => 1, // não retido
        ], fn ($v) => $v !== null && $v !== '');
    }
}
