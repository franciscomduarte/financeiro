<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conta_consumo_faturas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conta_consumo_id')->constrained('contas_consumo')->cascadeOnDelete();
            $table->string('competencia', 7);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->decimal('valor', 10, 2)->nullable();
            $table->jsonb('consumo')->nullable();
            $table->string('status')->default('pendente');
            $table->foreignUuid('transacao_id')->nullable()->constrained('transacoes')->nullOnDelete();
            $table->string('arquivo_path')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['conta_consumo_id', 'competencia']);
            $table->index(['conta_consumo_id', 'status']);
            $table->index(['status', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conta_consumo_faturas');
    }
};
