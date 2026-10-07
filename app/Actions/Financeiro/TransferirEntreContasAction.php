<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Models\ContaFinanceira;
use App\Models\Transferencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Move dinheiro entre contas da clínica (não é receita nem despesa). */
class TransferirEntreContasAction
{
    /** @param  array{conta_origem_id: string, conta_destino_id: string, data: string, valor: float|string, descricao?: ?string}  $dados */
    public function execute(array $dados): Transferencia
    {
        if ($dados['conta_origem_id'] === $dados['conta_destino_id']) {
            throw new RuntimeException('Escolha contas diferentes para a transferência.');
        }
        $valor = round((float) $dados['valor'], 2);
        if ($valor <= 0) {
            throw new RuntimeException('Informe um valor maior que zero.');
        }

        return DB::transaction(function () use ($dados, $valor): Transferencia {
            $contas = ContaFinanceira::query()->ativas()->whereKey([$dados['conta_origem_id'], $dados['conta_destino_id']])->count();
            if ($contas !== 2) {
                throw new RuntimeException('Conta não encontrada ou inativa.');
            }

            $transferencia = Transferencia::query()->create([
                'conta_origem_id'  => $dados['conta_origem_id'],
                'conta_destino_id' => $dados['conta_destino_id'],
                'data'             => $dados['data'],
                'valor'            => $valor,
                'descricao'        => $dados['descricao'] ?? null,
                'user_id'          => auth()->id(),
            ]);

            Log::info('[Financeiro] transferência entre contas', ['tenant_id' => $transferencia->tenant_id, 'user_id' => auth()->id(), 'valor' => $valor]);

            return $transferencia;
        });
    }
}
