<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatusTransacao;
use App\Models\Recorrencia;
use App\Models\Transacao;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cria os lançamentos das recorrências ativas da clínica até o fim do mês de referência.
 * Idempotente: a recorrência é travada durante a geração e (recorrencia_id, data_competencia) é único.
 */
class GerarLancamentosRecorrentesAction
{
    public function __construct(private readonly CreateTransacaoAction $criarLancamento) {}

    /** @return int quantidade de lançamentos criados */
    public function execute(?CarbonImmutable $referencia = null): int
    {
        $ate    = ($referencia ?? CarbonImmutable::today())->endOfMonth()->startOfDay();
        $total  = 0;

        Recorrencia::query()
            ->where('ativa', true)
            ->where('proxima_data', '<=', $ate->toDateString())
            ->select(['id'])
            ->lazyById(100)
            ->each(function (Recorrencia $r) use ($ate, &$total): void {
                $total += $this->gerar($r->id, $ate);
            });

        if ($total > 0) {
            Log::info('[Recorrencias] lançamentos gerados', ['quantidade' => $total, 'ate' => $ate->toDateString()]);
        }

        return $total;
    }

    /** Gera os lançamentos pendentes de uma recorrência até $ate (inclusive). */
    public function gerar(string $recorrenciaId, CarbonImmutable $ate): int
    {
        return DB::transaction(function () use ($recorrenciaId, $ate): int {
            $r = Recorrencia::query()->lockForUpdate()->find($recorrenciaId);
            if ($r === null || ! $r->ativa) {
                return 0;
            }

            $criados = 0;
            $data    = $r->proxima_data;

            while ($data->lte($ate)) {
                if ($r->data_fim !== null && $data->gt($r->data_fim)) {
                    break;
                }

                $jaExiste = Transacao::query()->where('recorrencia_id', $r->id)
                    ->whereDate('data_competencia', $data)->exists();

                if (! $jaExiste) {
                    $this->criarLancamento->execute([
                        'tipo'             => $r->tipo->value,
                        'fase'             => $r->fase->value,
                        'categoria'        => $r->categoria,
                        'descricao'        => $r->descricao,
                        'paciente_id'      => $r->paciente_id,
                        'fornecedor_id'    => $r->fornecedor_id,
                        'valor_bruto'      => (float) $r->valor_bruto,
                        'forma_pagamento'  => $r->forma_pagamento->value,
                        'data_competencia' => $data->toDateString(),
                        'status'           => $r->lancar_como_pago ? StatusTransacao::Pago->value : StatusTransacao::Pendente->value,
                        'data_pagamento'   => $r->lancar_como_pago ? $data->toDateString() : null,
                        'recorrencia'      => $r->frequencia->value,
                        'recorrencia_id'   => $r->id,
                        'observacoes'      => $r->observacoes,
                    ]);
                    $criados++;
                }

                $data = $r->dataSeguinte($data);
            }

            $atributos = ['proxima_data' => $data];
            if ($r->data_fim !== null && $data->gt($r->data_fim)) {
                // Chegou ao fim: não há mais o que gerar
                $atributos += ['ativa' => false, 'encerrada_em' => now()];
            }
            $r->update($atributos);

            return $criados;
        });
    }
}
