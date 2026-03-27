<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LlmService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Você é um assistente de extração de dados financeiros para um sistema de salão de beleza/estética.
Sua função é analisar transcrições de áudio e extrair dados estruturados.

Extraia os dados e retorne SOMENTE um objeto JSON válido, sem markdown, sem explicações, sem texto adicional.

Valores aceitos para cada campo:
- tipo: "entrada" ou "saida"
- fase: "implantacao" ou "operacao" (padrão: "operacao")
- forma_pagamento: "pix", "dinheiro", "debito", "credito_1x", "credito_2x", ... "credito_12x"
- status: "pago" ou "pendente" (se disser "já paguei", "pago", "já tá pago" → "pago")
- categoria (OBRIGATÓRIO — sempre infira a mais adequada com base na descrição):
  - Se tipo="entrada": "Procedimento Facial", "Depilação", "Massagem", "Skincare", "Produto Vendido", "Outros"
  - Se tipo="saida": "Infraestrutura", "Utilidades", "Marketing", "Burocracia", "Reforma", "Impostos", "Pessoal", "Insumos", "Outros"
  Exemplos: energia elétrica → "Utilidades"; salário → "Pessoal"; nota fiscal → "Burocracia"; limpeza facial → "Procedimento Facial"
- data_pagamento: se não informada explicitamente, use a data de hoje

Estrutura obrigatória do JSON:
{
  "tipo": "saida",
  "fase": "operacao",
  "categoria": "Utilidades",
  "descricao": "Resumo curto (máx 80 caracteres)",
  "valor_bruto": 150.00,
  "forma_pagamento": "pix",
  "data_competencia": "YYYY-MM-DD",
  "data_pagamento": "YYYY-MM-DD",
  "status": "pago",
  "observacoes": "Detalhes extras ou string vazia"
}
PROMPT;

    public function extrairDadosTransacao(string $texto, string $dataHoje): array
    {
        // Modo mock: ativado quando ANTHROPIC_API_KEY não está configurada
        if (app()->isLocal() && blank(config('services.anthropic.key'))) {
            return $this->mockExtracao($texto, $dataHoje);
        }

        $prompt = "Data de hoje: {$dataHoje}.\n\nTranscrição: {$texto}";

        $response = Http::withOptions(['verify' => ! app()->isLocal()])
            ->withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
                'max_tokens' => 512,
                'system'     => self::SYSTEM_PROMPT,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            Log::error('LlmService: falha na API Anthropic', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('Falha ao comunicar com a LLM: ' . $response->status());
        }

        $conteudo = $response->json('content.0.text', '');
        $conteudo = $this->limparJson($conteudo);
        $dados    = json_decode($conteudo, true);

        if (! is_array($dados)) {
            Log::error('LlmService: JSON inválido retornado pela LLM', ['raw' => $conteudo]);
            throw new RuntimeException('A LLM retornou um formato inesperado.');
        }

        return $dados;
    }

    /** Remove blocos de markdown (```json ... ```) que a LLM às vezes insere. */
    private function limparJson(string $texto): string
    {
        // Remove ```json ... ``` ou ``` ... ```
        $texto = preg_replace('/^```(?:json)?\s*/i', '', trim($texto));
        $texto = preg_replace('/\s*```$/', '', $texto);

        // Garante que começa no primeiro { e termina no último }
        $inicio = strpos($texto, '{');
        $fim    = strrpos($texto, '}');

        if ($inicio !== false && $fim !== false) {
            return substr($texto, $inicio, $fim - $inicio + 1);
        }

        return trim($texto);
    }

    /**
     * Extração local via regex para desenvolvimento sem API key.
     * Cobre os padrões mais comuns em português.
     */
    private function mockExtracao(string $texto, string $dataHoje): array
    {
        $lower = mb_strtolower($texto);
        $hoje  = now()->format('Y-m-d');

        // Tipo
        $tipo = str_contains($lower, 'recebi') || str_contains($lower, 'entrada') || str_contains($lower, 'recebimento')
            ? 'entrada' : 'saida';

        // Valor  (ex: "150 reais", "R$ 1.250,00", "mil e duzentos reais")
        $valor = null;
        if (preg_match('/r\$\s*([\d.,]+)/i', $texto, $m)) {
            $valor = (float) str_replace(['.', ','], ['', '.'], $m[1]);
        } elseif (preg_match('/([\d]+(?:[.,][\d]{2})?)\s*reais/i', $texto, $m)) {
            $valor = (float) str_replace(',', '.', $m[1]);
        }

        // Forma de pagamento
        $forma = 'pix';
        if (str_contains($lower, 'cartão') || str_contains($lower, 'cartao') || str_contains($lower, 'crédito') || str_contains($lower, 'credito')) {
            $forma = 'credito_1x';
        } elseif (str_contains($lower, 'débito') || str_contains($lower, 'debito')) {
            $forma = 'debito';
        } elseif (str_contains($lower, 'dinheiro') || str_contains($lower, 'espécie') || str_contains($lower, 'especie')) {
            $forma = 'dinheiro';
        } elseif (str_contains($lower, 'pix')) {
            $forma = 'pix';
        }

        // Status
        $status = (str_contains($lower, 'já paguei') || str_contains($lower, 'já tá pago') ||
                   str_contains($lower, 'ja paguei') || str_contains($lower, 'pago') ||
                   str_contains($lower, 'paguei')) ? 'pago' : 'pendente';

        // Descrição: remove ruído e pega a frase principal
        $descricao = preg_replace('/\b(gastei|paguei|recebi|reais|r\$|no pix|já tá pago|já paguei|hoje)\b/i', '', $texto);
        $descricao = trim(preg_replace('/\s+/', ' ', $descricao));
        $descricao = mb_substr(ucfirst(mb_strtolower($descricao)), 0, 80);

        return [
            'tipo'             => $tipo,
            'fase'             => 'operacao',
            'descricao'        => $descricao ?: 'Transação por voz',
            'valor_bruto'      => $valor,
            'forma_pagamento'  => $forma,
            'data_competencia' => $hoje,
            'data_pagamento'   => $status === 'pago' ? $hoje : null,
            'status'           => $status,
            'observacoes'      => '[MOCK] Texto original: ' . $texto,
        ];
    }
}
