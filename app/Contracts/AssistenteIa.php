<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Services\Assistente\RespostaAssistente;

/** Modelo de IA que conversa com o lead (Claude em produção; falso nos testes). */
interface AssistenteIa
{
    /**
     * @param  string  $instrucoes  parte fixa (treinamento da clínica): vai para o cache
     * @param  string  $contexto  parte que muda a cada resposta (data/hora, dados do lead)
     * @param  list<array{role: string, content: string}>  $mensagens  conversa, terminando na mensagem do lead
     * @param  list<array<string, mixed>>  $ferramentas  definições no formato da API (name, description, inputSchema)
     * @param  callable(string, array<string, mixed>): string  $executar  roda uma ferramenta e devolve o resultado em texto
     */
    public function responder(string $instrucoes, string $contexto, array $mensagens, array $ferramentas, callable $executar): RespostaAssistente;
}
