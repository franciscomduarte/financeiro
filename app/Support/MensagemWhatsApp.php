<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Texto de uma mensagem recebida pela Evolution (campo data.message), para a conversa do lead.
 * Abre os "envelopes" do WhatsApp (mensagem temporária, de visualização única, editada, documento
 * com legenda) e devolve null para o que não é conversa (reação, aviso de apagada, chaves internas).
 */
final class MensagemWhatsApp
{
    /** Envelopes que guardam a mensagem de verdade em ".message". */
    private const ENVELOPES = ['ephemeralMessage', 'viewOnceMessage', 'viewOnceMessageV2', 'viewOnceMessageV2Extension',
        'documentWithCaptionMessage', 'editedMessage', 'groupMentionedMessage', 'botInvokeMessage'];

    /** Não é conversa: não vira lead nem entra no histórico. */
    private const IGNORADAS = ['protocolMessage', 'reactionMessage', 'senderKeyDistributionMessage', 'pollUpdateMessage',
        'messageContextInfo', 'keepInChatMessage', 'pinInChatMessage', 'encReactionMessage'];

    /** Rótulo de quem manda só mídia ou outro conteúdo sem texto. */
    private const ROTULOS = [
        'audioMessage' => '[áudio]', 'pttMessage' => '[áudio]', 'imageMessage' => '[foto]', 'videoMessage' => '[vídeo]',
        'ptvMessage' => '[vídeo]', 'documentMessage' => '[documento]', 'stickerMessage' => '[figurinha]',
        'locationMessage' => '[localização]', 'liveLocationMessage' => '[localização]', 'contactMessage' => '[contato]',
        'contactsArrayMessage' => '[contatos]', 'pollCreationMessage' => '[enquete]', 'pollCreationMessageV3' => '[enquete]',
        'eventMessage' => '[evento]', 'callLogMesssage' => '[ligação]', 'lottieStickerMessage' => '[figurinha]',
    ];

    /**
     * @param  array<string, mixed>  $mensagem  data.message
     * @return string|null  texto, rótulo da mídia ("[foto]") ou null quando deve ser ignorada
     */
    public static function texto(array $mensagem, ?string $tipoInformado = null): ?string
    {
        $mensagem = self::abrirEnvelopes($mensagem);

        $texto = self::primeiroTexto($mensagem);
        if ($texto !== null) {
            return $texto;
        }

        $tipo = self::tipo($mensagem, $tipoInformado);
        if ($tipo === null || in_array($tipo, self::IGNORADAS, true)) {
            return null;
        }

        if ($tipo === 'locationMessage' || $tipo === 'liveLocationMessage') {
            $local = trim(implode(' · ', array_filter([$mensagem[$tipo]['name'] ?? null, $mensagem[$tipo]['address'] ?? null])));

            return $local !== '' ? "[localização] {$local}" : '[localização]';
        }
        if ($tipo === 'contactMessage' && filled($mensagem['contactMessage']['displayName'] ?? null)) {
            return '[contato] ' . $mensagem['contactMessage']['displayName'];
        }

        return self::ROTULOS[$tipo] ?? '[mensagem]';
    }

    /** Tipo da mensagem: o informado pela Evolution ou a primeira chave que não é metadado. */
    public static function tipo(array $mensagem, ?string $tipoInformado = null): ?string
    {
        $mensagem = self::abrirEnvelopes($mensagem);
        $chaves = array_values(array_diff(array_keys($mensagem), ['messageContextInfo', 'senderKeyDistributionMessage']));

        if ($chaves !== []) {
            return (string) $chaves[0];
        }

        return filled($tipoInformado) && ! in_array($tipoInformado, self::ENVELOPES, true) ? $tipoInformado : (array_keys($mensagem)[0] ?? null);
    }

    private static function abrirEnvelopes(array $mensagem): array
    {
        for ($i = 0; $i < 4; $i++) {
            $envelope = collect(self::ENVELOPES)->first(fn (string $e) => is_array($mensagem[$e]['message'] ?? null));
            if ($envelope === null) {
                // Mensagem editada (v2): o texto novo vem em protocolMessage.editedMessage
                if (is_array($mensagem['protocolMessage']['editedMessage'] ?? null)) {
                    $mensagem = $mensagem['protocolMessage']['editedMessage'];
                    continue;
                }
                break;
            }
            $mensagem = $mensagem[$envelope]['message'];
        }

        return $mensagem;
    }

    private static function primeiroTexto(array $m): ?string
    {
        $candidatos = [
            $m['conversation'] ?? null,
            $m['extendedTextMessage']['text'] ?? null,
            $m['imageMessage']['caption'] ?? null,
            $m['videoMessage']['caption'] ?? null,
            $m['documentMessage']['caption'] ?? null,
            $m['buttonsResponseMessage']['selectedDisplayText'] ?? null,
            $m['templateButtonReplyMessage']['selectedDisplayText'] ?? null,
            $m['listResponseMessage']['title'] ?? null,
            $m['interactiveResponseMessage']['body']['text'] ?? null,
        ];

        foreach ($candidatos as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
        }

        return null;
    }
}
