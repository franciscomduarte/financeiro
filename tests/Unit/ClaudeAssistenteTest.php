<?php

declare(strict_types=1);

namespace Tests\Unit;

use Anthropic\Client;
use App\Services\Assistente\ClaudeAssistente;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

/** Formato das chamadas ao Claude pelo SDK oficial (HTTP interceptado; nada sai para a internet). */
class ClaudeAssistenteTest extends TestCase
{
    private function mensagem(array $content, string $stopReason): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_' . uniqid(), 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-5-5',
            'content' => $content, 'stop_reason' => $stopReason, 'stop_sequence' => null,
            'usage' => ['input_tokens' => 120, 'output_tokens' => 30, 'cache_read_input_tokens' => 800, 'cache_creation_input_tokens' => 0],
        ]));
    }

    public function test_conversa_com_ferramenta_cache_e_reserva_automatica(): void
    {
        config(['services.anthropic.modelo_assistente' => 'claude-sonnet-5-5', 'services.anthropic.esforco_assistente' => 'low']);
        $enviadas = [];
        $pilha = HandlerStack::create(new MockHandler([
            $this->mensagem([['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'consultar_horarios', 'input' => ['a_partir_de' => null]]], 'tool_use'),
            $this->mensagem([['type' => 'text', 'text' => 'Tenho quinta às 9h. Pode ser?']], 'end_turn'),
        ]));
        $pilha->push(Middleware::history($enviadas));
        $cliente = new Client(apiKey: 'sk-teste', requestOptions: ['transporter' => new Guzzle(['handler' => $pilha]), 'maxRetries' => 0]);

        $usadas = [];
        $resposta = (new ClaudeAssistente($cliente))->responder(
            'INSTRUÇÕES FIXAS', 'Agora: hoje', [['role' => 'user', 'content' => 'Tem horário?']],
            [['name' => 'consultar_horarios', 'description' => 'Horários', 'inputSchema' => ['type' => 'object', 'properties' => ['a_partir_de' => ['type' => ['string', 'null']]], 'required' => ['a_partir_de']]]],
            function (string $nome, array $entrada) use (&$usadas): string {
                $usadas[] = $nome;

                return 'Quinta 09:00';
            },
        );

        $this->assertSame('Tenho quinta às 9h. Pode ser?', $resposta->texto);
        $this->assertSame(['consultar_horarios'], $usadas);
        $this->assertSame(240 + 1600, $resposta->tokensEntrada);
        $this->assertCount(2, $enviadas);

        $primeira = json_decode((string) $enviadas[0]['request']->getBody(), true);
        $this->assertSame('claude-sonnet-5-5', $primeira['model']);
        $this->assertSame('default', $primeira['fallbacks']);
        $this->assertSame(['effort' => 'low'], $primeira['output_config']);
        $this->assertSame(['type' => 'ephemeral'], $primeira['system'][0]['cache_control']);
        $this->assertArrayNotHasKey('cache_control', $primeira['system'][1]);
        $this->assertSame('consultar_horarios', $primeira['tools'][0]['name']);
        $this->assertArrayHasKey('input_schema', $primeira['tools'][0]);
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $enviadas[0]['request']->getHeaderLine('anthropic-beta'));
        $this->assertSame('sk-teste', $enviadas[0]['request']->getHeaderLine('x-api-key'));

        $segunda = json_decode((string) $enviadas[1]['request']->getBody(), true);
        $this->assertSame('assistant', $segunda['messages'][1]['role']);
        $this->assertSame('tool_use', $segunda['messages'][1]['content'][0]['type']);
        $this->assertSame(['type' => 'tool_result', 'tool_use_id' => 'toolu_1', 'content' => 'Quinta 09:00'], $segunda['messages'][2]['content'][0]);
    }

    public function test_recusa_vira_resposta_vazia_marcada(): void
    {
        $pilha = HandlerStack::create(new MockHandler([$this->mensagem([], 'refusal')]));
        $cliente = new Client(apiKey: 'sk-teste', requestOptions: ['transporter' => new Guzzle(['handler' => $pilha]), 'maxRetries' => 0]);

        $resposta = (new ClaudeAssistente($cliente))->responder('X', 'Y', [['role' => 'user', 'content' => 'oi']], [], fn () => '');

        $this->assertTrue($resposta->recusou);
        $this->assertSame('', $resposta->texto);
    }
}
