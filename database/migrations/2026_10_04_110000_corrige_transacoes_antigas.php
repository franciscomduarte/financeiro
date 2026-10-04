<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Correção única de dados após a revisão do módulo de transações:
 * - despesas não têm taxa de maquininha (só incide quando a clínica recebe);
 * - transação paga sempre tem data de pagamento (o Dashboard soma por ela).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $taxas = DB::table('transacoes')
                ->where('tipo', 'saida')
                ->where('taxa_operacional', '>', 0)
                ->update([
                    'taxa_operacional' => 0,
                    'valor_liquido'    => DB::raw('valor_bruto - imposto_estimado'),
                    'updated_at'       => now(),
                ]);

            $datas = DB::table('transacoes')
                ->where('status', 'pago')
                ->whereNull('data_pagamento')
                ->update([
                    'data_pagamento' => DB::raw('data_competencia'),
                    'updated_at'     => now(),
                ]);

            Log::info('[Migration] correção de transações antigas', [
                'saidas_sem_taxa'         => $taxas,
                'pagas_com_data_ajustada' => $datas,
            ]);
        });
    }

    public function down(): void
    {
        // Correção de dados: não há como restaurar os valores anteriores.
    }
};
