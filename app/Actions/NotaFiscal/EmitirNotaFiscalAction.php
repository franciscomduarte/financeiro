<?php

declare(strict_types=1);

namespace App\Actions\NotaFiscal;

use App\Enums\StatusNotaFiscal;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Jobs\EnviarNotaFiscalJob;
use App\Models\NotaFiscal;
use App\Models\Transacao;
use App\Support\ClinicaAtual;
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

    /** @param  array{discriminacao: string, tomador_nome: string, tomador_cpf?: ?string, tomador_email?: ?string}  $dados */
    public function execute(string $transacaoId, array $dados): NotaFiscal
    {
        $this->clinicaAtual->garantirEscrita();

        $clinica    = $this->clinicaAtual->get();
        $pendencias = $clinica->pendenciasNfse();
        if ($pendencias !== []) {
            throw new RuntimeException('Para emitir nota, complete em Dados da clínica › Nota fiscal: ' . implode(', ', $pendencias) . '.');
        }

        $cpf = preg_replace('/\D/', '', (string) ($dados['tomador_cpf'] ?? '')) ?: null;
        if ($cpf !== null && strlen($cpf) !== 11 && strlen($cpf) !== 14) {
            throw new RuntimeException('O CPF/CNPJ do tomador está incompleto.');
        }

        $nota = DB::transaction(function () use ($transacaoId, $dados, $cpf, $clinica): NotaFiscal {
            $transacao = Transacao::query()->select(['id', 'tipo', 'status', 'valor_bruto', 'paciente_id'])->lockForUpdate()->findOrFail($transacaoId);

            if ($transacao->tipo !== TipoTransacao::Entrada) {
                throw new RuntimeException('Nota fiscal só pode ser emitida para receitas.');
            }
            if ($transacao->status === StatusTransacao::Cancelado) {
                throw new RuntimeException('Este lançamento está cancelado.');
            }
            if ($transacao->notasFiscais()->whereIn('status', [StatusNotaFiscal::Processando, StatusNotaFiscal::Autorizada])->exists()) {
                throw new RuntimeException('Este lançamento já tem nota fiscal emitida ou em processamento.');
            }

            return NotaFiscal::create([
                'transacao_id'  => $transacao->id,
                'paciente_id'   => $transacao->paciente_id,
                'user_id'       => auth()->id(),
                'referencia'    => 'nf-' . Str::lower((string) Str::ulid()),
                'status'        => StatusNotaFiscal::Processando,
                'homologacao'   => $clinica->nfse_homologacao,
                'valor'         => $transacao->valor_bruto,
                'discriminacao' => trim($dados['discriminacao']),
                'tomador_nome'  => trim($dados['tomador_nome']),
                'tomador_cpf'   => $cpf,
                'tomador_email' => filled($dados['tomador_email'] ?? null) ? mb_strtolower(trim($dados['tomador_email'])) : null,
            ]);
        });

        EnviarNotaFiscalJob::dispatch($nota->id)->onQueue('default');

        Log::info('[NFS-e] emissão solicitada', ['nota_id' => $nota->id, 'transacao_id' => $nota->transacao_id, 'user_id' => auth()->id()]);

        return $nota;
    }
}
