<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * Financeiro, fase 3: recebíveis de cartão. A venda no cartão cai na conta Maquininha e vira
 * recebíveis (parcela a parcela ou antecipado) que são liberados para a conta bancária na data.
 * A conta Maquininha guarda os prazos, a taxa de antecipação (% ao mês) e a conta de liquidação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_financeiras', function (Blueprint $table): void {
            $table->unsignedSmallInteger('prazo_credito_dias')->default(30);
            $table->unsignedSmallInteger('prazo_debito_dias')->default(1);
            $table->unsignedSmallInteger('antecipacao_dias')->default(1);
            $table->decimal('taxa_antecipacao_mes', 5, 2)->default(0);   // % ao mês antecipado
            $table->boolean('antecipar_padrao')->default(false);
            $table->foreignUuid('conta_liquidacao_id')->nullable()->constrained('contas_financeiras')->nullOnDelete();
        });

        Schema::table('transacao_baixas', function (Blueprint $table): void {
            $table->boolean('antecipado')->nullable()->after('forma_pagamento');
        });

        Schema::create('recebiveis_cartao', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('transacao_baixa_id')->constrained('transacao_baixas')->cascadeOnDelete();
            $table->foreignUuid('transacao_id')->constrained('transacoes')->cascadeOnDelete();
            $table->foreignUuid('conta_maquininha_id')->constrained('contas_financeiras')->restrictOnDelete();
            $table->unsignedTinyInteger('parcela');
            $table->unsignedTinyInteger('total_parcelas');
            $table->boolean('antecipado')->default(false);
            $table->date('data_prevista');
            $table->decimal('valor', 12, 2);                    // já sem a taxa do cartão
            $table->decimal('taxa_antecipacao', 12, 2)->default(0);
            $table->decimal('valor_liquido', 12, 2);            // o que cai na conta
            $table->date('liquidado_em')->nullable();
            $table->foreignUuid('transferencia_id')->nullable()->constrained('transferencias')->nullOnDelete();
            $table->foreignUuid('despesa_id')->nullable()->constrained('transacoes')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'liquidado_em', 'data_prevista']);
        });

        // Conta do plano para a taxa de antecipação em todas as clínicas
        DB::transaction(function (): void {
            $agora = now();
            foreach (DB::table('clinicas')->pluck('id') as $clinicaId) {
                $existe = DB::table('plano_contas')->where('tenant_id', $clinicaId)->where('tipo', 'saida')->where('nome', 'Taxa de antecipação')->exists();
                if (! $existe && DB::table('plano_contas')->where('tenant_id', $clinicaId)->exists()) {
                    DB::table('plano_contas')->insert([
                        'id' => (string) Str::uuid(), 'tenant_id' => $clinicaId, 'nome' => 'Taxa de antecipação', 'tipo' => 'saida',
                        'grupo' => 'despesas_financeiras', 'ativa' => true, 'ordem' => 35, 'created_at' => $agora, 'updated_at' => $agora,
                    ]);
                }
                foreach (['Mensalidades' => 'receita_servicos'] as $nome => $grupo) {
                    if (DB::table('plano_contas')->where('tenant_id', $clinicaId)->exists()
                        && ! DB::table('plano_contas')->where('tenant_id', $clinicaId)->where('tipo', 'entrada')->where('nome', $nome)->exists()) {
                        DB::table('plano_contas')->insert([
                            'id' => (string) Str::uuid(), 'tenant_id' => $clinicaId, 'nome' => $nome, 'tipo' => 'entrada',
                            'grupo' => $grupo, 'ativa' => true, 'ordem' => 65, 'created_at' => $agora, 'updated_at' => $agora,
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recebiveis_cartao');
        Schema::table('transacao_baixas', fn (Blueprint $t) => $t->dropColumn('antecipado'));
        Schema::table('contas_financeiras', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('conta_liquidacao_id');
            $table->dropColumn(['prazo_credito_dias', 'prazo_debito_dias', 'antecipacao_dias', 'taxa_antecipacao_mes', 'antecipar_padrao']);
        });
    }
};
