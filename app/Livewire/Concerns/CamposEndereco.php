<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\CepService;

/**
 * Endereço em campos separados com busca pelo CEP (usado na ficha do paciente e na nota fiscal).
 * Os nomes seguem as colunas de pacientes: cep, logradouro, numero, complemento, bairro, cidade, uf, codigo_municipio.
 */
trait CamposEndereco
{
    public string $endCep             = '';
    public string $endLogradouro      = '';
    public string $endNumero          = '';
    public string $endComplemento     = '';
    public string $endBairro          = '';
    public string $endCidade          = '';
    public string $endUf              = '';
    public string $endCodigoMunicipio = '';
    public ?string $endAviso          = null;

    /** Ao completar os 8 números do CEP, preenche rua, bairro, cidade, UF e o código IBGE. */
    public function updatedEndCep(): void
    {
        $this->endAviso = null;
        $cep = (string) preg_replace('/\D/', '', $this->endCep);
        if (strlen($cep) !== 8) {
            $this->endCodigoMunicipio = '';

            return;
        }

        $endereco = app(CepService::class)->buscar($cep);
        if ($endereco === null) {
            $this->endCodigoMunicipio = '';
            $this->endAviso = 'Não encontramos esse CEP. Confira os números.';

            return;
        }

        $this->endCep             = substr($cep, 0, 5) . '-' . substr($cep, 5);
        $this->endLogradouro      = $endereco['logradouro'] ?: $this->endLogradouro;
        $this->endBairro          = $endereco['bairro'] ?: $this->endBairro;
        $this->endCidade          = $endereco['cidade'];
        $this->endUf              = $endereco['uf'];
        $this->endCodigoMunicipio = $endereco['codigo_municipio'];
    }

    /** @param  array<string, mixed>|object|null  $origem  paciente ou endereço salvo na nota */
    protected function preencherEndereco(array|object|null $origem): void
    {
        $v = fn (string $campo): string => (string) (is_array($origem) ? ($origem[$campo] ?? '') : ($origem->{$campo} ?? ''));

        $cep = $v('cep');
        $this->endCep             = strlen($cep) === 8 ? substr($cep, 0, 5) . '-' . substr($cep, 5) : $cep;
        $this->endLogradouro      = $v('logradouro');
        $this->endNumero          = $v('numero');
        $this->endComplemento     = $v('complemento');
        $this->endBairro          = $v('bairro');
        $this->endCidade          = $v('cidade');
        $this->endUf              = $v('uf');
        $this->endCodigoMunicipio = $v('codigo_municipio');
        $this->endAviso           = null;
    }

    protected function resetEndereco(): void
    {
        $this->preencherEndereco(null);
    }

    /** @return array<string, array<int, string>> */
    protected function regrasEndereco(): array
    {
        return [
            'endCep'         => ['nullable', 'regex:/^\d{5}-?\d{3}$/'],
            'endLogradouro'  => ['nullable', 'string', 'max:150'],
            'endNumero'      => ['nullable', 'string', 'max:20'],
            'endComplemento' => ['nullable', 'string', 'max:80'],
            'endBairro'      => ['nullable', 'string', 'max:80'],
            'endCidade'      => ['nullable', 'string', 'max:80'],
            'endUf'          => ['nullable', 'regex:/^[A-Za-z]{2}$/'],
        ];
    }

    /** @return array<string, string> */
    protected function mensagensEndereco(): array
    {
        return [
            'endCep.regex' => 'Use os 8 números do CEP. Ex.: 71900-100.',
            'endUf.regex'  => 'Use a sigla do estado. Ex.: DF.',
        ];
    }

    /** @return array{cep: ?string, logradouro: ?string, numero: ?string, complemento: ?string, bairro: ?string, cidade: ?string, uf: ?string, codigo_municipio: ?string} */
    protected function dadosEndereco(): array
    {
        $t = fn (string $valor): ?string => trim($valor) !== '' ? trim($valor) : null;

        return [
            'cep'              => preg_replace('/\D/', '', $this->endCep) ?: null,
            'logradouro'       => $t($this->endLogradouro),
            'numero'           => $t($this->endNumero),
            'complemento'      => $t($this->endComplemento),
            'bairro'           => $t($this->endBairro),
            'cidade'           => $t($this->endCidade),
            'uf'               => $t(mb_strtoupper($this->endUf)),
            'codigo_municipio' => preg_match('/^\d{7}$/', $this->endCodigoMunicipio) ? $this->endCodigoMunicipio : null,
        ];
    }
}
