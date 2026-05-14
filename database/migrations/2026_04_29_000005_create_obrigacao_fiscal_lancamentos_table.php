<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obrigacao_fiscal_lancamentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('obrigacao_fiscal_id')->constrained('obrigacoes_fiscais')->cascadeOnDelete();
            $table->string('competencia', 7);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->decimal('valor_principal', 10, 2);
            $table->decimal('valor_multa', 10, 2)->default(0);
            $table->decimal('valor_juros', 10, 2)->default(0);
            $table->string('status')->default('pendente');
            $table->string('numero_autenticacao', 50)->nullable();
            $table->string('codigo_barras', 60)->nullable();
            $table->foreignUuid('transacao_id')->nullable()->constrained('transacoes')->nullOnDelete();
            $table->string('arquivo_path')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['obrigacao_fiscal_id', 'competencia']);
            $table->index(['obrigacao_fiscal_id', 'status']);
            $table->index(['status', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obrigacao_fiscal_lancamentos');
    }
};
