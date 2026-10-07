<?php

declare(strict_types=1);

namespace App\Services\Assistente;

use Anthropic\Beta\Messages\BetaTextBlock;
use Anthropic\Beta\Messages\BetaToolUseBlock;
use Anthropic\Client;
use App\Contracts\AssistenteIa;
use RuntimeException;

/** Assistente com o Claude (SDK oficial): conversa + ferramentas da clínica, até 4 rodadas de ferramenta. */
class ClaudeAssistente implements AssistenteIa
{
    private const MAX_RODADAS = 4;

    public function __construct(private readonly Client $client) {}

    public function responder(string $instrucoes, string $contexto, array $mensagens, array $ferramentas, callable $executar): RespostaAssistente
    {
        $modelo = (string) config('services.anthropic.modelo_assistente');
        // Parte fixa no cache; data/hora e dados do lead depois do ponto de cache
        $system = [
            ['type' => 'text', 'text' => $instrucoes, 'cacheControl' => ['type' => 'ephemeral']],
            ['type' => 'text', 'text' => $contexto],
        ];
        $entrada = $saida = 0;

        for ($rodada = 0; ; $rodada++) {
            $resposta = $this->client->beta->messages->create(
                maxTokens: 4000,
                messages: $mensagens,
                model: $modelo,
                system: $system,
                tools: $ferramentas ?: null,
                outputConfig: ['effort' => (string) config('services.anthropic.esforco_assistente', 'low')],
                // Se o modelo recusar por segurança, a própria API tenta outro modelo
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
            $entrada += $resposta->usage->inputTokens + (int) $resposta->usage->cacheReadInputTokens + (int) $resposta->usage->cacheCreationInputTokens;
            $saida   += $resposta->usage->outputTokens;

            if ($resposta->stopReason === 'refusal') {
                return new RespostaAssistente('', $entrada, $saida, recusou: true);
            }
            if ($resposta->stopReason !== 'tool_use') {
                break;
            }
            if ($rodada >= self::MAX_RODADAS) {
                throw new RuntimeException('Assistente passou do limite de rodadas de ferramenta.');
            }

            $resultados = [];
            foreach ($resposta->content as $bloco) {
                if ($bloco instanceof BetaToolUseBlock) {
                    $resultados[] = ['type' => 'tool_result', 'toolUseID' => $bloco->id, 'content' => $executar($bloco->name, $bloco->input)];
                }
            }
            // Conteúdo devolvido como veio (inclui blocos de raciocínio), depois todos os resultados numa só mensagem
            $mensagens[] = ['role' => 'assistant', 'content' => $resposta->content];
            $mensagens[] = ['role' => 'user', 'content' => $resultados];
        }

        $texto = '';
        foreach ($resposta->content as $bloco) {
            if ($bloco instanceof BetaTextBlock) {
                $texto .= $bloco->text;
            }
        }

        return new RespostaAssistente(trim($texto), $entrada, $saida);
    }
}
