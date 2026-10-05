<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PadraoNfse;
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
 * Padrão municipal em /v2/nfse e NFS-e Nacional em /v2/nfsen. Documentação: https://focusnfe.com.br/doc/#nfse
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
    public function emitir(PadraoNfse $padrao, string $referencia, array $nota): array
    {
        $resposta = $this->cliente()->post($padrao->endpoint() . '?ref=' . urlencode($referencia), $nota);
        $this->registrar('envio', $padrao, $referencia, $resposta);

        if ($resposta->status() === 422 && ($resposta->json('codigo') === 'nfe_ja_existente' || str_contains((string) $resposta->json('mensagem'), 'já foi'))) {
            return ['status' => 'processando_autorizacao', 'mensagem' => null]; // reenvio da mesma ref: segue consultando
        }

        if ($resposta->failed()) {
            return ['status' => 'erro_autorizacao', 'mensagem' => $this->mensagemDeErro($resposta) ?? 'A Focus NFe recusou a nota (HTTP ' . $resposta->status() . ').'];
        }

        return ['status' => (string) $resposta->json('status', 'processando_autorizacao'), 'mensagem' => null];
    }

    /**
     * @return array{status: string, numero: ?string, codigo_verificacao: ?string, url: ?string, url_xml: ?string, mensagem: ?string}
     */
    public function consultar(PadraoNfse $padrao, string $referencia): array
    {
        $resposta = $this->cliente()->get($padrao->endpoint() . '/' . urlencode($referencia));

        if ($resposta->status() === 404) {
            // Logo após o envio a Focus pode ainda não ter registrado a nota; quem decide se desiste é a consulta.
            $this->registrar('consulta', $padrao, $referencia, $resposta);

            return ['status' => 'nao_encontrado', 'numero' => null, 'codigo_verificacao' => null, 'url' => null, 'url_xml' => null,
                'mensagem' => $this->mensagemDeErro($resposta)];
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
    public function cancelar(PadraoNfse $padrao, string $referencia, string $justificativa): array
    {
        $resposta = $this->cliente()->delete($padrao->endpoint() . '/' . urlencode($referencia), ['justificativa' => $justificativa]);
        $this->registrar('cancelamento', $padrao, $referencia, $resposta);

        if ($resposta->failed()) {
            return ['status' => 'erro_cancelamento', 'mensagem' => $this->mensagemDeErro($resposta)];
        }

        return ['status' => (string) $resposta->json('status', 'cancelado'), 'mensagem' => $this->mensagemDeErro($resposta)];
    }

    /** Registra a resposta da Focus (sem o corpo da nota, que tem dados do paciente). */
    private function registrar(string $operacao, PadraoNfse $padrao, string $referencia, Response $resposta): void
    {
        Log::log($resposta->successful() ? 'info' : 'warning', '[NFS-e] resposta da Focus NFe', [
            'tenant_id'  => $this->clinica()->id,
            'operacao'   => $operacao,
            'padrao'     => $padrao->value,
            'referencia' => $referencia,
            'http'       => $resposta->status(),
            'status'     => $resposta->json('status'),
            'codigo'     => $resposta->json('codigo'),
            'mensagem'   => $this->mensagemDeErro($resposta),
        ]);
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
