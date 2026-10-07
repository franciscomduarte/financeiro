<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipoTransacao;
use App\Models\ContaFinanceira;
use App\Models\TransacaoBaixa;
use App\Models\Transferencia;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Saldo das contas = saldo inicial + recebimentos − pagamentos ± transferências.
 * Só conta o que é posterior à data do saldo inicial (o ajuste de saldo "zera" o histórico anterior).
 */
class SaldoContasService
{
    /**
     * Saldo de cada conta ao fim do dia $ate (padrão: hoje).
     *
     * @return Collection<string, float>  conta_id => saldo
     */
    public function saldos(?CarbonInterface $ate = null): Collection
    {
        $ate    = ($ate ?? today())->toDateString();
        $contas = ContaFinanceira::query()->select(['id', 'saldo_inicial', 'saldo_inicial_em'])->limit(200)->get()->keyBy('id');

        $saldos = $contas->map(fn (ContaFinanceira $c) => (float) $c->saldo_inicial);

        $baixas = TransacaoBaixa::query()
            ->join('contas_financeiras as cf', 'cf.id', '=', 'transacao_baixas.conta_financeira_id')
            ->where('transacao_baixas.data', '<=', $ate)
            ->where(fn ($q) => $q->whereNull('cf.saldo_inicial_em')->orWhereColumn('transacao_baixas.data', '>', 'cf.saldo_inicial_em'))
            ->groupBy('transacao_baixas.conta_financeira_id', 'transacao_baixas.tipo')
            ->selectRaw('transacao_baixas.conta_financeira_id as conta, transacao_baixas.tipo as tipo, SUM(transacao_baixas.valor_movimentado) as total')
            ->get();

        foreach ($baixas as $b) {
            $sinal = $b->getRawOriginal('tipo') === TipoTransacao::Entrada->value ? 1 : -1;
            $saldos[$b->conta] = ($saldos[$b->conta] ?? 0) + $sinal * (float) $b->total;
        }

        foreach (['conta_origem_id' => -1, 'conta_destino_id' => 1] as $coluna => $sinal) {
            $transferencias = Transferencia::query()
                ->join('contas_financeiras as cf', 'cf.id', '=', "transferencias.{$coluna}")
                ->where('transferencias.data', '<=', $ate)
                ->where(fn ($q) => $q->whereNull('cf.saldo_inicial_em')->orWhereColumn('transferencias.data', '>', 'cf.saldo_inicial_em'))
                ->groupBy("transferencias.{$coluna}")
                ->selectRaw("transferencias.{$coluna} as conta, SUM(transferencias.valor) as total")
                ->get();
            foreach ($transferencias as $tr) {
                $saldos[$tr->conta] = ($saldos[$tr->conta] ?? 0) + $sinal * (float) $tr->total;
            }
        }

        return $saldos->map(fn ($v) => round((float) $v, 2));
    }

    public function saldo(ContaFinanceira $conta, ?CarbonInterface $ate = null): float
    {
        return (float) ($this->saldos($ate)[$conta->id] ?? 0);
    }
}
