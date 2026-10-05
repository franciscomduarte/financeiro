<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ClinicaNaoDefinidaException;
use App\Models\Clinica;
use App\Support\ClinicaAtual;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cliente da API v2 da Focus NFe (NFS-e), com o token e o ambiente da clínica ativa.
 * Documentação: https://focusnfe.com.br/doc/#nfse
 */
class FocusNfeService
{
    private const URL_PRODUCAO    = 'https://api.focusnfe.com.br';
    private const URL_HOMOLOGACAO = 'https://homologacao.focusnfe.com.br';

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /**
     * Envia a nota para autorização (assíncrona na Focus: depois é preciso consultar).
     *
     * @param  array<string, mixed>  $nota
     * @return array{status: string, mensagem: ?string}
     */
    public function emitir(string $referencia, array $nota): array
    {
        $resposta = $this->cliente()->post('/v2/nfse?ref=' . urlencode($referencia), $nota);

        if ($resposta->status() === 422 && ($resposta->json('codigo') === 'nfe_ja_existente' || str_contains((string) $resposta->json('mensagem'), 'já foi'))) {
            return ['status' => 'processando_autorizacao', 'mensagem' => null]; // reenvio da mesma ref: segue consultando
        }

        if ($resposta->failed()) {
            return ['status' => 'erro_autorizacao', 'mensagem' => $this->mensagemDeErro($resposta)];
        }

        return ['status' => (string) $resposta->json('status', 'processando_autorizacao'), 'mensagem' => null];
    }

    /**
     * @return array{status: string, numero: ?string, codigo_verificacao: ?string, url: ?string, url_xml: ?string, mensagem: ?string}
     */
    public function consultar(string $referencia): array
    {
        $resposta = $this->cliente()->get('/v2/nfse/' . urlencode($referencia));

        if ($resposta->status() === 404) {
            return ['status' => 'erro_autorizacao', 'numero' => null, 'codigo_verificacao' => null, 'url' => null, 'url_xml' => null,
                'mensagem' => 'A Focus NFe não encontrou esta nota. Emita de novo.'];
        }
        $resposta->throw();

        $xml = $resposta->json('caminho_xml_nota_fiscal');

        return [
            'status'             => (string) $resposta->json('status'),
            'numero'             => $resposta->json('numero') !== null ? (string) $resposta->json('numero') : null,
            'codigo_verificacao' => $resposta->json('codigo_verificacao'),
            'url'                => $resposta->json('url_danfse') ?? $resposta->json('url'),
            'url_xml'            => $xml ? ($this->baseUrl() . $xml) : null,
            'mensagem'           => $this->mensagemDeErro($resposta),
        ];
    }

    /** @return array{status: string, mensagem: ?string} */
    public function cancelar(string $referencia, string $justificativa): array
    {
        $resposta = $this->cliente()->delete('/v2/nfse/' . urlencode($referencia), ['justificativa' => $justificativa]);

        if ($resposta->failed()) {
            return ['status' => 'erro_cancelamento', 'mensagem' => $this->mensagemDeErro($resposta)];
        }

        return ['status' => (string) $resposta->json('status', 'cancelado'), 'mensagem' => $this->mensagemDeErro($resposta)];
    }

    /** Erros vêm como {"mensagem"} ou {"erros": [{"mensagem", "correcao"}]}. */
    private function mensagemDeErro(Response $resposta): ?string
    {
        $erros = collect($resposta->json('erros') ?? [])
            ->map(fn ($e) => trim(($e['mensagem'] ?? '') . (! empty($e['correcao']) ? ' (' . $e['correcao'] . ')' : '')))
            ->filter();

        $mensagem = $erros->isNotEmpty() ? $erros->implode(' | ') : $resposta->json('mensagem');

        return $mensagem ? mb_substr((string) $mensagem, 0, 1000) : null;
    }

    private function cliente(): PendingRequest
    {
        $clinica = $this->clinica();
        if (! filled($clinica->nfse_token)) {
            throw new RuntimeException('Cadastre o token da Focus NFe em Dados da clínica › Nota fiscal.');
        }

        return Http::baseUrl($this->baseUrl())
            ->withBasicAuth((string) $clinica->nfse_token, '')
            ->acceptJson()
            ->timeout(20)
            ->retry(3, 500, fn ($e) => $e instanceof ConnectionException, throw: false)
            ->beforeSending(fn () => Log::info('[NFS-e] chamada à Focus NFe', ['tenant_id' => $clinica->id]));
    }

    private function baseUrl(): string
    {
        return $this->clinica()->nfse_homologacao ? self::URL_HOMOLOGACAO : self::URL_PRODUCAO;
    }

    private function clinica(): Clinica
    {
        return $this->clinicaAtual->get() ?? throw new ClinicaNaoDefinidaException('NFS-e');
    }
}
