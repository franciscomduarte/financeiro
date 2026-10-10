<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MensagemWhatsApp;
use PHPUnit\Framework\TestCase;

/** Texto das mensagens recebidas pela Evolution, inclusive as que vêm dentro de "envelopes". */
class MensagemWhatsAppTest extends TestCase
{
    public function test_le_texto_de_todos_os_formatos(): void
    {
        $this->assertSame('Oi', MensagemWhatsApp::texto(['conversation' => 'Oi']));
        $this->assertSame('Vi o anúncio', MensagemWhatsApp::texto(['extendedTextMessage' => ['text' => 'Vi o anúncio']]));
        // Mensagem temporária e de visualização única
        $this->assertSame('Quanto custa?', MensagemWhatsApp::texto(['ephemeralMessage' => ['message' => ['extendedTextMessage' => ['text' => 'Quanto custa?']]]], 'ephemeralMessage'));
        $this->assertSame('Olha essa', MensagemWhatsApp::texto(['viewOnceMessageV2' => ['message' => ['imageMessage' => ['caption' => 'Olha essa']]]]));
        $this->assertSame('[foto]', MensagemWhatsApp::texto(['ephemeralMessage' => ['message' => ['imageMessage' => ['url' => 'x']]]]));
        // Editada, documento com legenda, resposta de botão
        $this->assertSame('Corrigido', MensagemWhatsApp::texto(['protocolMessage' => ['type' => 14, 'editedMessage' => ['conversation' => 'Corrigido']]]));
        $this->assertSame('Meu exame', MensagemWhatsApp::texto(['documentWithCaptionMessage' => ['message' => ['documentMessage' => ['caption' => 'Meu exame']]]]));
        $this->assertSame('Quero agendar', MensagemWhatsApp::texto(['buttonsResponseMessage' => ['selectedDisplayText' => 'Quero agendar']]));
        // Mídia sem texto e outros conteúdos
        $this->assertSame('[áudio]', MensagemWhatsApp::texto(['audioMessage' => ['seconds' => 5], 'messageContextInfo' => []], 'audioMessage'));
        $this->assertSame('[localização] Clínica · Rua 12', MensagemWhatsApp::texto(['locationMessage' => ['name' => 'Clínica', 'address' => 'Rua 12']]));
        $this->assertSame('[contato] Maria', MensagemWhatsApp::texto(['contactMessage' => ['displayName' => 'Maria']]));
        $this->assertSame('[mensagem]', MensagemWhatsApp::texto(['algoNovoMessage' => []]));
    }

    public function test_ignora_o_que_nao_e_conversa(): void
    {
        $this->assertNull(MensagemWhatsApp::texto(['reactionMessage' => ['text' => '❤️']], 'reactionMessage'));
        $this->assertNull(MensagemWhatsApp::texto(['protocolMessage' => ['type' => 0]], 'protocolMessage')); // apagada
        $this->assertNull(MensagemWhatsApp::texto(['senderKeyDistributionMessage' => ['x' => 1]]));
        $this->assertNull(MensagemWhatsApp::texto([]));
    }
}
