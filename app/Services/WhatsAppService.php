<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl;
    private string $apiKey;
    private string $instance;

    public function __construct()
    {
        $this->baseUrl  = rtrim((string) config('evolution.url'), '/');
        $this->apiKey   = (string) config('evolution.api_key');
        $this->instance = (string) config('evolution.instance');
    }

    public function enviarCobranca(string $telefone, array $cobranca, string $nomePaciente): bool
    {
        $numero = $this->formatarTelefone($telefone);

        $this->enviarTexto($numero, $this->montarMensagem($nomePaciente, $cobranca));

        // Imagem do QR Code — apenas se o Asaas retornar o base64
        if (!empty($cobranca['qr_code_base64'])) {
            $this->enviarImagem($numero, $cobranca['qr_code_base64'], 'QR Code Pix');
        }

        return true;
    }

    public function enviarLembrete(string $telefone, string $nomePaciente, float $valor, string $vencimento): bool
    {
        $numero         = $this->formatarTelefone($telefone);
        $valorFormatado = number_format($valor, 2, ',', '.');

        $mensagem = "⏰ *Lembrete de pagamento*\n\n"
            . "Olá, {$nomePaciente}!\n\n"
            . "Sua mensalidade de *R$ {$valorFormatado}* vence *amanhã ({$vencimento})*.\n\n"
            . "Caso já tenha pago, desconsidere esta mensagem. 😊";

        return $this->enviarTexto($numero, $mensagem);
    }

    private function montarMensagem(string $nome, array $cobranca): string
    {
        $valor    = number_format((float) $cobranca['valor'], 2, ',', '.');
        $isParcela = ! empty($cobranca['parcela_info']);

        $titulo = $isParcela
            ? "💚 *Parcela {$cobranca['parcela_info']} - LC Estética*"
            : "💚 *Cobrança mensal - LC Estética*";

        $msg = "{$titulo}\n\n"
            . "Olá, *{$nome}*!\n\n";

        if ($isParcela) {
            $msg .= "Segue sua cobrança de parcelamento:\n";
            if (! empty($cobranca['descricao'])) {
                $msg .= "📝 Ref.: *{$cobranca['descricao']}*\n";
            }
        } else {
            $msg .= "Segue sua cobrança do mês:\n";
        }

        $msg .= "💰 Valor: *R$ {$valor}*\n"
            . "📅 Vencimento: *{$cobranca['vencimento']}*\n";

        if (!empty($cobranca['link_fatura'])) {
            $msg .= "\n🔗 *Clique para pagar:*\n{$cobranca['link_fatura']}\n";
        }

        if (!empty($cobranca['qr_code_texto'])) {
            $msg .= "\n📋 *Pix Copia e Cola:*\n```{$cobranca['qr_code_texto']}```\n";
        }

        $msg .= "\nEm caso de dúvidas, entre em contato. 🙏";

        return $msg;
    }

    private function enviarTexto(string $numero, string $mensagem): bool
    {
        $resposta = Http::withHeaders(['apikey' => $this->apiKey])
            ->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
                'number'      => $numero,
                'textMessage' => ['text' => $mensagem],
            ]);

        if ($resposta->failed()) {
            Log::warning('WhatsApp: falha ao enviar texto', [
                'numero'   => $numero,
                'response' => $resposta->body(),
            ]);
            return false;
        }

        return true;
    }

    private function enviarImagem(string $numero, string $base64, string $caption = ''): bool
    {
        $resposta = Http::withHeaders(['apikey' => $this->apiKey])
            ->post("{$this->baseUrl}/message/sendMedia/{$this->instance}", [
                'number'      => $numero,
                'mediatype'   => 'image',
                'mimetype'    => 'image/png',
                'caption'     => $caption,
                'media'       => $base64,
            ]);

        return $resposta->successful();
    }

    private function formatarTelefone(string $telefone): string
    {
        $numero = preg_replace('/\D/', '', $telefone) ?? '';

        if (strlen($numero) <= 11) {
            $numero = '55' . $numero;
        }

        return $numero;
    }
}
