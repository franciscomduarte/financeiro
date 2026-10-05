<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FormaPagamento;
use App\Models\Recorrencia;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Alterações numa recorrência. Mudanças de valor e dados valem dos próximos lançamentos em diante;
 * os já criados continuam como estão (cada um pode ser editado na lista de lançamentos).
 */
class GerenciarRecorrenciaAction
{
    public function __construct(private readonly GerarLancamentosRecorrentesAction $gerar) {}

    /** @param  array{descricao: string, categoria: string, valor_bruto: float, forma_pagamento: string, data_fim: ?string, lancar_como_pago: bool, observacoes: ?string}  $dados */
    public function atualizar(Recorrencia $recorrencia, array $dados): Recorrencia
    {
        if (FormaPagamento::from($dados['forma_pagamento'])->parcelas() > 1) {
            throw new InvalidArgumentException('Lançamentos parcelados no cartão não podem se repetir. Use outra forma de pagamento.');
        }

        return DB::transaction(function () use ($recorrencia, $dados): Recorrencia {
            $recorrencia->update($dados);

            // Data final antes da próxima ocorrência: não há mais nada a gerar
            if ($recorrencia->data_fim !== null && $recorrencia->data_fim->lt($recorrencia->proxima_data) && ! $recorrencia->encerrada()) {
                $recorrencia->update(['ativa' => false, 'encerrada_em' => now()]);
            }

            $this->registrar('atualizada', $recorrencia, array_keys($recorrencia->getChanges()));

            return $recorrencia;
        });
    }

    public function pausar(Recorrencia $recorrencia): void
    {
        $recorrencia->update(['ativa' => false]);
        $this->registrar('pausada', $recorrencia);
    }

    /** Volta a gerar a partir do mês atual (meses em que ficou pausada não são criados). */
    public function retomar(Recorrencia $recorrencia): void
    {
        if ($recorrencia->encerrada()) {
            throw new InvalidArgumentException('Esta recorrência foi encerrada. Crie um novo lançamento repetido se precisar.');
        }

        DB::transaction(function () use ($recorrencia): void {
            $inicioDoMes = CarbonImmutable::today()->startOfMonth();
            $proxima     = $recorrencia->proxima_data;
            while ($proxima->lt($inicioDoMes)) {
                $proxima = $recorrencia->dataSeguinte($proxima);
            }

            $recorrencia->update(['ativa' => true, 'proxima_data' => $proxima]);
            $this->gerar->gerar($recorrencia->id, CarbonImmutable::today()->endOfMonth()->startOfDay());
        });

        $this->registrar('retomada', $recorrencia);
    }

    /** Para de vez: nenhum lançamento novo. Os já criados continuam na lista. */
    public function encerrar(Recorrencia $recorrencia): void
    {
        $recorrencia->update(['ativa' => false, 'encerrada_em' => now()]);
        $this->registrar('encerrada', $recorrencia);
    }

    private function registrar(string $acao, Recorrencia $recorrencia, array $campos = []): void
    {
        Log::info("[Recorrencias] recorrência {$acao}", array_filter([
            'recorrencia_id' => $recorrencia->id,
            'user_id'        => auth()->id(),
            'campos'         => $campos ?: null,
        ]));
    }
}
