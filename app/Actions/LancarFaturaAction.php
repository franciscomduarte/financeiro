<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatusFatura;
use App\Models\ContaConsumo;
use App\Models\ContaConsumoFatura;
use Illuminate\Support\Facades\DB;

class LancarFaturaAction
{
    public function execute(ContaConsumo $conta, array $data): ContaConsumoFatura
    {
        return DB::transaction(function () use ($conta, $data): ContaConsumoFatura {
            $consumo = null;
            if (!empty($data['consumo_chave']) && isset($data['consumo_valor']) && $data['consumo_valor'] !== '') {
                $consumo = [$data['consumo_chave'] => (float) $data['consumo_valor']];
            }

            $status = (!empty($data['valor']))
                ? StatusFatura::Recebida
                : StatusFatura::Pendente;

            $fatura = ContaConsumoFatura::create([
                'conta_consumo_id' => $conta->id,
                'competencia'      => $data['competencia'],
                'data_vencimento'  => $data['data_vencimento'],
                'valor'            => !empty($data['valor']) ? (float) $data['valor'] : null,
                'consumo'          => $consumo,
                'status'           => $status,
                'observacoes'      => $data['observacoes'] ?? null,
            ]);
            // Com valor, já entra no contas a pagar
            app(\App\Actions\Financeiro\TitulosContasFixasAction::class)->fatura($fatura);

            return $fatura->fresh();
        });
    }
}
