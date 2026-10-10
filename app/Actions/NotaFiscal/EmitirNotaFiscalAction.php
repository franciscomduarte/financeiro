<?php

declare(strict_types=1);

namespace App\Actions\NotaFiscal;

use App\Enums\StatusNotaFiscal;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Jobs\EnviarNotaFiscalJob;
use App\Models\NotaFiscal;
use App\Models\Paciente;
use App\Models\Transacao;
use App\Support\ClinicaAtual;
use App\Support\Documento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pede a emissão da NFS-e de uma receita. A nota fica "processando" e a fila envia para a
 * Focus NFe e acompanha até a prefeitura autorizar (ou recusar).
 */
class EmitirNotaFiscalAction
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /** Campos do endereço que a prefeitura exige quando ele vai na nota. */
    private const ENDERECO_OBRIGATORIO = ['cep' => 'CEP', 'logradouro' => 'rua', 'numero' => 'número', 'bairro' => 'bairro', 'uf' => 'UF', 'codigo_municipio' => 'cidade pelo CEP'];

    /** @param  array{discriminacao: string, tomador_nome: string, tomador_cpf?: ?string, tomador_email?: ?string, tomador_endereco?: ?array<string, ?string>}  $dados */
    public function execute(string $transacaoId, array $dados): NotaFiscal
    {
        $this->clinicaAtual->garantirEscrita();

        $clinica    = $this->clinicaAtual->get();
        $pendencias = $clinica->pendenciasNfse();
        if ($pendencias !== []) {
            throw new RuntimeException('Para emitir nota, complete em Dados da clínica › Nota fiscal: ' . implode(', ', $pendencias) . '.');
        }

        $cpf = Documento::numeros($dados['tomador_cpf'] ?? null) ?: null;
        if ($cpf !== null && strlen($cpf) !== 11 && strlen($cpf) !== 14) {
            throw new RuntimeException('O CPF/CNPJ do tomador está incompleto.');
        }
        if ($cpf !== null && ! Documento::valido($cpf)) {
            throw new RuntimeException('O ' . (strlen($cpf) === 14 ? 'CNPJ' : 'CPF') . ' do tomador não é válido. Confira os números.');
        }

        $endereco = $this->endereco($dados['tomador_endereco'] ?? null);

        $nota = DB::transaction(function () use ($transacaoId, $dados, $cpf, $endereco, $clinica): NotaFiscal {
            $transacao = Transacao::query()->select(['id', 'tipo', 'status', 'valor_bruto', 'paciente_id', 'data_competencia'])->lockForUpdate()->findOrFail($transacaoId);

            if ($transacao->tipo !== TipoTransacao::Entrada) {
                throw new RuntimeException('Nota fiscal só pode ser emitida para receitas.');
            }
            if ($transacao->status === StatusTransacao::Cancelado) {
                throw new RuntimeException('Este lançamento está cancelado.');
            }
            if ($transacao->notasFiscais()->whereIn('status', [StatusNotaFiscal::Processando, StatusNotaFiscal::Autorizada])->exists()) {
                throw new RuntimeException('Este lançamento já tem nota fiscal emitida ou em processamento.');
            }

            $nota = NotaFiscal::create([
                'transacao_id'  => $transacao->id,
                'paciente_id'   => $transacao->paciente_id,
                'user_id'       => auth()->id(),
                'referencia'    => 'nf-' . Str::lower((string) Str::ulid()),
                'status'        => StatusNotaFiscal::Processando,
                'homologacao'   => $clinica->nfse_homologacao,
                'padrao'        => $clinica->nfse_padrao,
                'valor'         => $transacao->valor_bruto,
                'data_competencia' => $transacao->data_competencia,
                'discriminacao' => trim($dados['discriminacao']),
                'tomador_nome'  => trim($dados['tomador_nome']),
                'tomador_cpf'   => $cpf,
                'tomador_email' => filled($dados['tomador_email'] ?? null) ? mb_strtolower(trim($dados['tomador_email'])) : null,
                'tomador_endereco' => $endereco,
            ]);

            // Paciente sem endereço ganha o que foi digitado na nota (a próxima já vem preenchida)
            if ($endereco !== null && $transacao->paciente_id !== null) {
                Paciente::query()->whereKey($transacao->paciente_id)->whereNull('cep')->update($endereco);
            }

            return $nota;
        });

        EnviarNotaFiscalJob::dispatch($nota->id)->onQueue('default');

        Log::info('[NFS-e] emissão solicitada', ['nota_id' => $nota->id, 'transacao_id' => $nota->transacao_id, 'user_id' => auth()->id()]);

        return $nota;
    }

    /**
     * Endereço vazio não vai na nota; incompleto é recusado (a prefeitura rejeitaria).
     *
     * @param  array<string, ?string>|null  $endereco
     * @return array<string, string>|null
     */
    private function endereco(?array $endereco): ?array
    {
        $campos = ['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'codigo_municipio'];
        $limpo  = array_filter(
            array_map(fn ($v) => trim((string) $v), array_intersect_key($endereco ?? [], array_flip($campos))),
            fn (string $v) => $v !== '',
        );
        if (isset($limpo['cep'])) {
            $limpo['cep'] = Documento::numeros($limpo['cep']);
        }
        if (array_diff_key($limpo, ['cidade' => 1, 'uf' => 1, 'codigo_municipio' => 1]) === []) {
            return null; // nada digitado além do que o CEP preencheria
        }

        $faltando = array_values(array_diff_key(self::ENDERECO_OBRIGATORIO, $limpo));
        if (strlen($limpo['cep'] ?? '') !== 8) {
            $faltando = array_unique(['CEP', ...$faltando]);
        }
        if ($faltando !== []) {
            throw new RuntimeException('Complete o endereço do tomador (falta: ' . implode(', ', $faltando) . ') ou deixe todos os campos em branco.');
        }

        return $limpo;
    }
}
