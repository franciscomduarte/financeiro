<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AsaasService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey  = (string) config('asaas.api_key');
        $this->baseUrl = config('asaas.sandbox')
            ? 'https://sandbox.asaas.com/api/v3'
            : 'https://api.asaas.com/v3';
    }

    public function obterOuCriarCliente(array $paciente): string
    {
        $resposta = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/customers", ['cpfCnpj' => $paciente['cpf']]);

        if ($resposta->successful() && $resposta->json('totalCount') > 0) {
            return (string) $resposta->json('data.0.id');
        }

        $resposta = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/customers", [
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
            ->post("{$this->baseUrl}/payments", [
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

    public function consultarStatus(string $pagamentoId): string
    {
        $resposta = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/payments/{$pagamentoId}");

        return (string) ($resposta->json('status') ?? 'UNKNOWN');
    }

    public function buscarQrCode(string $pagamentoId): array
    {
        $resposta = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/payments/{$pagamentoId}/pixQrCode");

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
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}
