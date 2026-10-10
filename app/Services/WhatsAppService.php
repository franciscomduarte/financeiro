<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ClinicaNaoDefinidaException;
use App\Support\ClinicaAtual;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl;

    /** Id da última mensagem aceita pela Evolution (para acompanhar entrega e leitura). */
    public ?string $ultimoIdMensagem = null;

    /** Servidor Evolution é único; instância e chave são de cada clínica. */
    public function __construct(private readonly ClinicaAtual $clinicaAtual)
    {
        $this->baseUrl = rtrim((string) config('evolution.url'), '/');
    }

    /** @return array{0: string, 1: string}|null [instância, chave] da clínica ativa */
    private function credenciais(): ?array
    {
        $clinica = $this->clinicaAtual->get() ?? throw new ClinicaNaoDefinidaException('WhatsApp');

        if (! $clinica->whatsappConfigurado()) {
            Log::warning('WhatsApp: clínica sem instância configurada; mensagem não enviada', ['tenant_id' => $clinica->id]);
            return null;
        }

        return [$clinica->evolution_instance, $clinica->evolution_api_key];
    }

    /** Nome da clínica ativa para os textos das mensagens. */
    private function nomeClinica(): string
    {
        return $this->clinicaAtual->get()?->nome ?? (string) config('app.name');
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
            ? "💚 *Parcela {$cobranca['parcela_info']} - {$this->nomeClinica()}*"
            : "💚 *Cobrança mensal - {$this->nomeClinica()}*";

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

    /** Texto para o telefone como está no cadastro (com máscara, sem DDI). */
    public function enviarTextoParaTelefone(string $telefone, string $mensagem): bool
    {
        return $this->enviarTexto($this->formatarTelefone($telefone), $mensagem);
    }

    public function enviarTexto(string $numero, string $mensagem): bool
    {
        // v2 recebe o texto direto; v1 dentro de textMessage
        $resposta = $this->enviar('sendText', (int) config('evolution.versao') >= 2
            ? ['number' => $numero, 'text' => $mensagem]
            : ['number' => $numero, 'textMessage' => ['text' => $mensagem]]);

        if ($resposta === null) {
            return false;
        }

        if ($resposta->failed()) {
            Log::warning('WhatsApp: falha ao enviar texto', [
                'numero'   => $numero,
                'response' => $resposta->body(),
            ]);
            return false;
        }

        return true;
    }

    public function enviarConfirmacaoAgendamento(\App\Models\Agendamento $agendamento): bool
    {
        $numero    = $this->formatarTelefone($agendamento->paciente->telefone);
        $data      = $agendamento->inicio_em->translatedFormat('l, d \d\e F');
        $hora      = $agendamento->inicio_em->format('H:i');
        $nome      = $agendamento->paciente->nome;
        $proc      = $agendamento->procedimento->nome;
        $prof      = $agendamento->profissional->nome;

        $mensagem = "📅 *Agendamento Confirmado — {$this->nomeClinica()}*\n\n"
            . "Olá, *{$nome}*!\n\n"
            . "Seu agendamento foi confirmado:\n"
            . "✂️ Procedimento: *{$proc}*\n"
            . "👩‍⚕️ Profissional: *{$prof}*\n"
            . "📆 Data: *{$data}*\n"
            . "🕐 Horário: *{$hora}*\n\n"
            . "Em caso de dúvidas, entre em contato. 🙏";

        return $this->enviarTexto($numero, $mensagem);
    }

    public function enviarCancelamentoAgendamento(\App\Models\Agendamento $agendamento): bool
    {
        $numero  = $this->formatarTelefone($agendamento->paciente->telefone);
        $nome    = $agendamento->paciente->nome;
        $proc    = $agendamento->procedimento->nome;
        $data    = $agendamento->inicio_em->translatedFormat('d \d\e F');
        $hora    = $agendamento->inicio_em->format('H:i');
        $motivo  = $agendamento->motivo_cancelamento;

        $mensagem = "❌ *Agendamento Cancelado — {$this->nomeClinica()}*\n\n"
            . "Olá, *{$nome}*!\n\n"
            . "Seu agendamento de *{$proc}* do dia *{$data}* às *{$hora}* foi cancelado."
            . ($motivo ? "\n\n📝 Motivo: {$motivo}" : '')
            . "\n\nPara reagendar, entre em contato conosco. 🙏";

        return $this->enviarTexto($numero, $mensagem);
    }

    public function enviarReagendamentoAgendamento(\App\Models\Agendamento $agendamento): bool
    {
        $numero = $this->formatarTelefone($agendamento->paciente->telefone);
        $nome   = $agendamento->paciente->nome;
        $proc   = $agendamento->procedimento->nome;
        $prof   = $agendamento->profissional->nome;
        $data   = $agendamento->inicio_em->translatedFormat('l, d \d\e F');
        $hora   = $agendamento->inicio_em->format('H:i');

        $mensagem = "🔄 *Reagendamento Confirmado — {$this->nomeClinica()}*\n\n"
            . "Olá, *{$nome}*!\n\n"
            . "Seu agendamento foi reagendado:\n"
            . "✂️ Procedimento: *{$proc}*\n"
            . "👩‍⚕️ Profissional: *{$prof}*\n"
            . "📆 Nova data: *{$data}*\n"
            . "🕐 Novo horário: *{$hora}*\n\n"
            . "Em caso de dúvidas, entre em contato. 🙏";

        return $this->enviarTexto($numero, $mensagem);
    }

    public function enviarLembreteAgendamento(\App\Models\Agendamento $agendamento, string $quando): bool
    {
        $numero = $this->formatarTelefone($agendamento->paciente->telefone);
        $nome   = $agendamento->paciente->nome;
        $proc   = $agendamento->procedimento->nome;
        $prof   = $agendamento->profissional->nome;
        $hora   = $agendamento->inicio_em->format('H:i');
        $data   = $agendamento->inicio_em->translatedFormat('d \d\e F');

        $quando === 'amanhã'
            ? $preambulo = "sua consulta é *amanhã*!"
            : $preambulo = "sua consulta é *hoje daqui a pouco*!";

        $mensagem = "⏰ *Lembrete — {$this->nomeClinica()}*\n\n"
            . "Olá, *{$nome}*, {$preambulo}\n\n"
            . "✂️ Procedimento: *{$proc}*\n"
            . "👩‍⚕️ Profissional: *{$prof}*\n"
            . "📆 Data: *{$data}*\n"
            . "🕐 Horário: *{$hora}*\n\n"
            . "Se precisar cancelar, nos avise com antecedência. 🙏";

        return $this->enviarTexto($numero, $mensagem);
    }

    public function enviarDocumento(
        string $numero,
        string $base64,
        string $mimeType,
        string $nomeArquivo,
        string $caption = '',
    ): bool {
        $resposta = $this->enviar('sendMedia', [
            'number'    => $this->formatarTelefone($numero),
            'mediatype' => 'document',
            'mimetype'  => $mimeType,
            'caption'   => $caption,
            'media'     => $base64,
            'fileName'  => $nomeArquivo,
        ]);

        return (bool) $resposta?->successful();
    }

    private function enviarImagem(string $numero, string $base64, string $caption = ''): bool
    {
        $resposta = $this->enviar('sendMedia', [
            'number'      => $numero,
            'mediatype'   => 'image',
            'mimetype'    => 'image/png',
            'caption'     => $caption,
            'media'       => $base64,
        ]);

        return (bool) $resposta?->successful();
    }

    /** POST na instância da clínica ativa, com 2 novas tentativas em erro de conexão/5xx. Null = sem credenciais. */
    private function enviar(string $acao, array $payload): ?Response
    {
        $this->ultimoIdMensagem = null;
        if ($this->clinicaAtual->leituraPorPerfil()) {
            $this->clinicaAtual->garantirEscrita(); // perfil "Somente consulta" não manda mensagem
        }
        $credenciais = $this->credenciais();
        if ($credenciais === null) {
            return null;
        }
        [$instancia, $chave] = $credenciais;

        $resposta = Http::withHeaders(['apikey' => $chave])
            ->retry(3, 500, fn ($e) => ! $e instanceof RequestException || $e->response->serverError(), throw: false)
            ->post("{$this->baseUrl}/message/{$acao}/" . rawurlencode($instancia), $payload);

        if ($resposta->successful()) {
            $id = $resposta->json('key.id');
            $this->ultimoIdMensagem = is_string($id) && $id !== '' ? $id : null;
        }

        return $resposta;
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
