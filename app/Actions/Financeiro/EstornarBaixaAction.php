<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Models\Transacao;
use App\Models\TransacaoBaixa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Desfaz um pagamento/recebimento registrado por engano: o lançamento volta a ter o valor em aberto. */
class EstornarBaixaAction
{
    public function execute(TransacaoBaixa $baixa): void
    {
        DB::transaction(function () use ($baixa): void {
            /** @var Transacao $t */
            $t = Transacao::query()->lockForUpdate()->findOrFail($baixa->transacao_id);
            $baixa->delete();
            $t->recalcularPagamento();

            Log::info('[Financeiro] baixa estornada', [
                'tenant_id' => $t->tenant_id, 'user_id' => auth()->id(), 'transacao_id' => $t->id, 'valor' => (float) $baixa->valor,
            ]);
        });
    }
}
