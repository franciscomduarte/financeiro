<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lançamentos recorrentes: cada recorrência é o modelo (aluguel, salário, internet...) a partir do
 * qual o sistema cria os lançamentos de cada mês. O par (recorrencia_id, data_competencia) é único,
 * o que torna a geração idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recorrencias', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->string('tipo', 10);
            $table->string('fase', 20);
            $table->string('categoria', 100);
            $table->string('descricao', 255);
            $table->foreignUuid('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->foreignUuid('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->decimal('valor_bruto', 10, 2);
            $table->string('forma_pagamento', 30);
            $table->string('frequencia', 20);
            $table->unsignedTinyInteger('dia_vencimento');
            $table->date('proxima_data');
            $table->date('data_fim')->nullable();
            $table->boolean('lancar_como_pago')->default(false);
            $table->boolean('ativa')->default(true);
            $table->timestamp('encerrada_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['ativa', 'proxima_data']);
        });

        Schema::table('transacoes', function (Blueprint $table): void {
            $table->foreignUuid('recorrencia_id')->nullable()->after('transacao_pai_id')
                ->constrained('recorrencias')->nullOnDelete();
            $table->unique(['recorrencia_id', 'data_competencia']);
        });
    }

    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table): void {
            $table->dropUnique(['recorrencia_id', 'data_competencia']);
            $table->dropConstrainedForeignId('recorrencia_id');
        });
        Schema::dropIfExists('recorrencias');
    }
};
