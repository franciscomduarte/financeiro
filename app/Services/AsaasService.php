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
        sleep(2);
        $qrCode      = $this->buscarQrCode($pagamentoId);

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

        return $resposta->json() ?? [];
    }

    private function headers(): array
    {
        return [
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}
