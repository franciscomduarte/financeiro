<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Models\ContaFinanceira;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "O saldo desta conta em tal dia era X": vira o saldo inicial da conta naquela data, e só os
 * movimentos posteriores contam a partir daí. Usado na implantação e para acertar diferenças.
 */
class AjustarSaldoContaAction
{
    public function execute(ContaFinanceira $conta, float $saldo, string $data): void
    {
        DB::transaction(function () use ($conta, $saldo, $data): void {
            $conta->forceFill(['saldo_inicial' => round($saldo, 2), 'saldo_inicial_em' => $data])->save();
        });

        Log::info('[Financeiro] saldo ajustado', ['tenant_id' => $conta->tenant_id, 'user_id' => auth()->id(), 'conta' => $conta->id, 'saldo' => $saldo, 'data' => $data]);
    }
}
