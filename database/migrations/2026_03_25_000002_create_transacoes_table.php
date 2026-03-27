<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tipo', 10);
            $table->string('fase', 20);
            $table->string('categoria', 100);
            $table->string('subcategoria', 100)->nullable();
            $table->string('centro_custo', 100)->nullable();
            $table->string('descricao', 255);
            $table->string('cliente', 150)->nullable();
            $table->uuid('fornecedor_id')->nullable();
            $table->decimal('valor_bruto', 10, 2);
            $table->decimal('taxa_operacional', 10, 2)->default(0);
            $table->decimal('imposto_estimado', 10, 2)->default(0);
            $table->decimal('valor_liquido', 10, 2);
            $table->date('data_competencia');
            $table->date('data_pagamento')->nullable();
            $table->string('forma_pagamento', 30);
            $table->integer('num_parcelas')->default(1);
            $table->integer('parcela_atual')->default(1);
            $table->string('status', 20)->default('pendente');
            $table->string('recorrencia', 20)->default('unica');
            $table->date('data_inicio_recorrencia')->nullable();
            $table->uuid('transacao_pai_id')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('fase');
            $table->index('tipo');
            $table->index('data_competencia');
            $table->index(['data_competencia', 'status']);
            $table->index('categoria');
            $table->index('forma_pagamento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes');
    }
};
