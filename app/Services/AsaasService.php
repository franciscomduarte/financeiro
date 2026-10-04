<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ClinicaNaoDefinidaException;
use App\Models\Clinica;
use App\Support\ClinicaAtual;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AsaasService
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /** Conta Asaas da clínica ativa (cada clínica cobra na própria conta). */
    private function clinica(): Clinica
    {
        $clinica = $this->clinicaAtual->get() ?? throw new ClinicaNaoDefinidaException('Asaas');

        if (! $clinica->asaasConfigurado()) {
            throw new RuntimeException("A clínica {$clinica->nome} não tem a chave do Asaas configurada (Configurações → Integrações).");
        }

        return $clinica;
    }

    private function baseUrl(): string
    {
        return $this->clinica()->asaas_sandbox
            ? 'https://sandbox.asaas.com/api/v3'
            : 'https://api.asaas.com/v3';
    }

    public function obterOuCriarCliente(array $paciente): string
    {
        $resposta = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl()}/customers", ['cpfCnpj' => $paciente['cpf']]);

        if ($resposta->successful() && $resposta->json('totalCount') > 0) {
            return (string) $resposta->json('data.0.id');
        }

        $resposta = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl()}/customers", [
                'name'        => $paciente['nome'],
                'cpfCnpj'     => $paciente['cpf'],
                'email'       => $paciente['email'],
                'mobilePhone' => $paciente['telefone'] ?? null,
            ]);

        if ($resposta->failed()) {
            Log::error('Asaas: erro ao criar cliente', $resposta->json() ?? []);
            throw new RuntimeException('Erro ao criar cliente no Asaas: ' . $resposta->body());
        }

        return (string) $resposta->json('id');
    }

    public function gerarCobrancaPix(string $clienteId, float $valor, string $descricao, Carbon $vencimento): array
    {
        $resposta = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl()}/payments", [
                'customer'    => $clienteId,
                'billingType' => 'PIX',
                'value'       => $valor,
                'dueDate'     => $vencimento->format('Y-m-d'),
                'description' => $descricao,
            ]);

        if ($resposta->failed()) {
            Log::error('Asaas: erro ao gerar cobrança', $resposta->json() ?? []);
            throw new RuntimeException('Erro ao gerar cobrança: ' . $resposta->body());
        }

        $pagamentoId = (string) $resposta->json('id');
        $qrCode      = $this->buscarQrCodeComRetry($pagamentoId);

        return [
            'pagamento_id'   => $pagamentoId,
            'valor'          => $valor,
            'vencimento'     => $vencimento->format('d/m/Y'),
            'status'         => (string) $resposta->json('status'),
            'link_fatura'    => $resposta->json('invoiceUrl'),
            'qr_code_base64' => $qrCode['encodedImage'] ?? null,
            'qr_code_texto'  => $qrCode['payload'] ?? null,
        ];
    }

    /** Confere se a chave da clínica é aceita pelo Asaas (tela de configurações). */
    public function testarConexao(): bool
    {
        $resposta = Http::withHeaders($this->headers())
            ->timeout(10)
            ->get("{$this->baseUrl()}/customers", ['limit' => 1]);

        if ($resposta->failed()) {
            Log::warning('Asaas: teste de conexão falhou', ['status' => $resposta->status()]);
        }

        return $resposta->successful();
    }

    public function consultarStatus(string $pagamentoId): string
    {
        $resposta = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl()}/payments/{$pagamentoId}");

        return (string) ($resposta->json('status') ?? 'UNKNOWN');
    }

    public function buscarQrCode(string $pagamentoId): array
    {
        $resposta = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl()}/payments/{$pagamentoId}/pixQrCode");

        $data = $resposta->json() ?? [];

        Log::info('Asaas pixQrCode response', [
            'pagamento_id' => $pagamentoId,
            'status'       => $resposta->status(),
            'tem_payload'  => !empty($data['payload']),
            'tem_imagem'   => !empty($data['encodedImage']),
            'erros'        => $data['errors'] ?? null,
        ]);

        return $data;
    }

    private function buscarQrCodeComRetry(string $pagamentoId, int $tentativas = 5): array
    {
        $esperas = [2, 3, 5, 8, 10]; // segundos entre cada tentativa

        foreach ($esperas as $i => $espera) {
            sleep($espera);

            $qrCode = $this->buscarQrCode($pagamentoId);

            if (!empty($qrCode['payload'])) {
                return $qrCode;
            }

            Log::info("Asaas QR Code não disponível ainda. Tentativa " . ($i + 1) . " de {$tentativas}.", [
                'pagamento_id' => $pagamentoId,
            ]);

            if ($i + 1 >= $tentativas) {
                break;
            }
        }

        Log::warning('Asaas: QR Code não disponível após todas as tentativas.', [
            'pagamento_id' => $pagamentoId,
        ]);

        return [];
    }

    private function headers(): array
    {
        return [
            'access_token' => $this->clinica()->asaas_api_key,
            'Content-Type' => 'application/json',
        ];
    }
}
