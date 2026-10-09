<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Endereço a partir do CEP (ViaCEP), com o código IBGE do município que a nota fiscal exige.
 * CEP é dado público e igual para todas as clínicas: o cache é global.
 */
class CepService
{
    private const URL = 'https://viacep.com.br/ws/%s/json/';

    /** @return array{cep: string, logradouro: string, bairro: string, cidade: string, uf: string, codigo_municipio: string}|null */
    public function buscar(string $cep): ?array
    {
        $cep = (string) preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) {
            return null;
        }

        $encontrado = Cache::get("cep:{$cep}");
        if ($encontrado !== null) {
            return $encontrado;
        }

        try {
            $resposta = Http::acceptJson()->timeout(5)
                ->retry(2, 300, fn ($e) => $e instanceof ConnectionException, throw: false)
                ->get(sprintf(self::URL, $cep));
        } catch (Throwable $e) {
            Log::warning('[CEP] consulta falhou', ['cep' => $cep, 'erro' => $e->getMessage()]);

            return null;
        }

        if ($resposta->failed() || $resposta->json('erro') || ! $resposta->json('ibge')) {
            if ($resposta->failed()) {
                Log::warning('[CEP] ViaCEP respondeu com erro', ['cep' => $cep, 'http' => $resposta->status()]);
            }

            return null;
        }

        $endereco = [
            'cep'              => $cep,
            'logradouro'       => (string) $resposta->json('logradouro'),
            'bairro'           => (string) $resposta->json('bairro'),
            'cidade'           => (string) $resposta->json('localidade'),
            'uf'               => (string) $resposta->json('uf'),
            'codigo_municipio' => (string) $resposta->json('ibge'),
        ];
        Cache::put("cep:{$cep}", $endereco, now()->addDays(30));

        return $endereco;
    }
}
