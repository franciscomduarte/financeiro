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
            $transacao = $pagamento->transacao_id ? Transacao::query()->find($pagamento->transacao_id) : null;
            $pagamento->delete();

            // Conta a pagar do contrato: volta a ficar em aberto; lançamento avulso: é removido
            if ($transacao?->contrato_id !== null) {
                app(\App\Actions\UpdateTransacaoAction::class)->execute($transacao, ['status' => \App\Enums\StatusTransacao::Pendente->value, 'data_pagamento' => null]);
            } elseif ($transacao !== null) {
                $transacao->delete();
            }
        });
    }
}
