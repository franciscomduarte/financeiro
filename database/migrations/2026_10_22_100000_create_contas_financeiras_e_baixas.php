<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Financeiro, fase 1: contas financeiras (caixa, banco, maquininha) com saldo, vencimento nos
 * lançamentos e baixas (pagamentos e recebimentos, inclusive parciais, com juros, multa e desconto).
 *
 * Os dados existentes são preservados: cada clínica ganha as contas padrão, o vencimento passa a ser
 * a competência e cada lançamento pago ganha uma baixa (dinheiro no Caixa, o resto na conta bancária).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_financeiras', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->string('nome', 80);
            $table->string('tipo', 20);                          // caixa, banco, maquininha, outra
            $table->string('banco', 80)->nullable();
            $table->string('agencia', 20)->nullable();
            $table->string('numero', 30)->nullable();
            $table->decimal('saldo_inicial', 12, 2)->default(0);
            $table->date('saldo_inicial_em')->nullable();
            $table->boolean('padrao')->default(false);           // sugerida para PIX, boleto e transferências
            $table->boolean('ativa')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->unique(['tenant_id', 'nome']);
        });

        Schema::table('transacoes', function (Blueprint $table): void {
            $table->date('data_vencimento')->nullable()->after('data_competencia');
            $table->decimal('valor_pago', 12, 2)->default(0)->after('valor_liquido');
            $table->foreignUuid('conta_financeira_id')->nullable()->after('forma_pagamento')
                ->constrained('contas_financeiras')->nullOnDelete();

            $table->index(['tenant_id', 'tipo', 'status', 'data_vencimento']);
        });

        Schema::create('transacao_baixas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('transacao_id')->constrained('transacoes')->cascadeOnDelete();
            $table->foreignUuid('conta_financeira_id')->constrained('contas_financeiras')->restrictOnDelete();
            $table->string('tipo', 10);                          // entrada ou saida (igual ao lançamento)
            $table->date('data');
            $table->decimal('valor', 12, 2);                     // quanto abate do lançamento
            $table->decimal('juros', 12, 2)->default(0);
            $table->decimal('multa', 12, 2)->default(0);
            $table->decimal('desconto', 12, 2)->default(0);
            $table->decimal('taxa', 12, 2)->default(0);          // retida pela maquininha (só entradas)
            $table->decimal('valor_movimentado', 12, 2);         // o que entrou ou saiu da conta
            $table->string('forma_pagamento', 30)->nullable();
            $table->string('observacoes', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'conta_financeira_id', 'data']);
            $table->index(['tenant_id', 'data']);
        });

        Schema::create('transferencias', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('conta_origem_id')->constrained('contas_financeiras')->restrictOnDelete();
            $table->foreignUuid('conta_destino_id')->constrained('contas_financeiras')->restrictOnDelete();
            $table->date('data');
            $table->decimal('valor', 12, 2);
            $table->string('descricao', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'data']);
        });

        DB::transaction(function (): void {
            $this->migrarDados();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias');
        Schema::dropIfExists('transacao_baixas');
        Schema::table('transacoes', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'tipo', 'status', 'data_vencimento']);
            $table->dropConstrainedForeignId('conta_financeira_id');
            $table->dropColumn(['data_vencimento', 'valor_pago']);
        });
        Schema::dropIfExists('contas_financeiras');
    }

    private function migrarDados(): void
    {
        DB::statement('UPDATE transacoes SET data_vencimento = data_competencia WHERE data_vencimento IS NULL');

        $agora = now();

        foreach (DB::table('clinicas')->pluck('id') as $clinicaId) {
            $contas = [
                'caixa'      => ['nome' => 'Caixa', 'padrao' => false],
                'banco'      => ['nome' => 'Conta bancária', 'padrao' => true],
                'maquininha' => ['nome' => 'Maquininha', 'padrao' => false],
            ];
            $ids = [];
            foreach ($contas as $tipo => $conta) {
                $ids[$tipo] = (string) Str::uuid();
                DB::table('contas_financeiras')->insert([
                    'id' => $ids[$tipo], 'tenant_id' => $clinicaId, 'nome' => $conta['nome'], 'tipo' => $tipo,
                    'saldo_inicial' => 0, 'padrao' => $conta['padrao'], 'ativa' => true,
                    'created_at' => $agora, 'updated_at' => $agora,
                ]);
            }

            // Lançamentos já pagos ganham uma baixa (o histórico de cartão já caiu no banco)
            DB::statement("
                INSERT INTO transacao_baixas (id, tenant_id, transacao_id, conta_financeira_id, tipo, data, valor,
                    juros, multa, desconto, taxa, valor_movimentado, forma_pagamento, created_at, updated_at)
                SELECT gen_random_uuid(), t.tenant_id, t.id,
                    CASE WHEN t.forma_pagamento = 'dinheiro' THEN ?::uuid ELSE ?::uuid END,
                    t.tipo, COALESCE(t.data_pagamento, t.data_competencia), t.valor_bruto, 0, 0, 0,
                    CASE WHEN t.tipo = 'entrada' THEN COALESCE(t.taxa_operacional, 0) ELSE 0 END,
                    t.valor_bruto - CASE WHEN t.tipo = 'entrada' THEN COALESCE(t.taxa_operacional, 0) ELSE 0 END,
                    t.forma_pagamento, ?, ?
                FROM transacoes t
                WHERE t.tenant_id = ? AND t.status = 'pago'
            ", [$ids['caixa'], $ids['banco'], $agora, $agora, $clinicaId]);
        }

        DB::statement("UPDATE transacoes SET valor_pago = valor_bruto WHERE status = 'pago'");
    }
};
