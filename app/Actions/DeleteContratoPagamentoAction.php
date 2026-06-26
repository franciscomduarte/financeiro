<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ContratoPagamento;
use App\Models\Transacao;
use Illuminate\Support\Facades\DB;

class DeleteContratoPagamentoAction
{
    public function execute(ContratoPagamento $pagamento): void
    {
        DB::transaction(function () use ($pagamento): void {
            $transacaoId = $pagamento->transacao_id;
            $pagamento->delete();

            if ($transacaoId) {
                Transacao::destroy($transacaoId);
            }
        });
    }
}
