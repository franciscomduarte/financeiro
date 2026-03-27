<?php

declare(strict_types=1);

namespace App\Services;

class TransacaoExtracaoService
{
    private const FORMAS_PAGAMENTO = [
        'pix', 'dinheiro', 'debito',
        'credito_1x',  'credito_2x',  'credito_3x',  'credito_4x',
        'credito_5x',  'credito_6x',  'credito_7x',  'credito_8x',
        'credito_9x',  'credito_10x', 'credito_11x', 'credito_12x',
    ];

    private const CATEGORIAS = [
        'entrada' => ['Procedimento Facial', 'Depilação', 'Massagem', 'Skincare', 'Produto Vendido', 'Outros'],
        'saida'   => ['Infraestrutura', 'Utilidades', 'Marketing', 'Burocracia', 'Reforma', 'Impostos', 'Pessoal', 'Insumos', 'Outros'],
    ];

    public function __construct(private readonly LlmService $llm) {}

    /** Extrai dados de um texto livre usando a LLM e retorna array normalizado. */
    public function extrair(string $texto): array
    {
        $dados = $this->llm->extrairDadosTransacao($texto, now()->format('d/m/Y'));

        return $this->normalizar($dados);
    }

    /** Normaliza e valida os dados brutos da LLM para os enums/formatos do sistema. */
    public function normalizar(array $dados): array
    {
        $tipo  = in_array($dados['tipo'] ?? '', ['entrada', 'saida'], true) ? $dados['tipo'] : 'saida';
        $hoje  = now()->format('Y-m-d');

        $categoriasValidas = self::CATEGORIAS[$tipo];
        $categoriaLlm      = (string) ($dados['categoria'] ?? '');
        $categoria         = in_array($categoriaLlm, $categoriasValidas, true) ? $categoriaLlm : 'Outros';

        return [
            'tipo'             => $tipo,
            'fase'             => in_array($dados['fase'] ?? '', ['implantacao', 'operacao'], true)
                                    ? $dados['fase'] : 'operacao',
            'categoria'        => $categoria,
            'descricao'        => mb_substr((string) ($dados['descricao'] ?? ''), 0, 80),
            'valor_bruto'      => is_numeric($dados['valor_bruto'] ?? null)
                                    ? round((float) $dados['valor_bruto'], 2) : null,
            'forma_pagamento'  => in_array($dados['forma_pagamento'] ?? '', self::FORMAS_PAGAMENTO, true)
                                    ? $dados['forma_pagamento'] : 'pix',
            'data_competencia' => $this->validarData($dados['data_competencia'] ?? null) ?? $hoje,
            'data_pagamento'   => $this->validarData($dados['data_pagamento'] ?? null)   ?? $hoje,
            'status'           => in_array($dados['status'] ?? '', ['pago', 'pendente', 'cancelado'], true)
                                    ? $dados['status'] : 'pendente',
            'observacoes'      => (string) ($dados['observacoes'] ?? ''),
        ];
    }

    private function validarData(?string $data): ?string
    {
        if (! $data) {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $data);
        return ($d && $d->format('Y-m-d') === $data) ? $data : null;
    }
}
