<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_consumo', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->string('tipo');
            $table->string('descricao');
            $table->unsignedTinyInteger('dia_vencimento');
            $table->decimal('valor_estimado', 10, 2)->nullable();
            $table->string('status')->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_consumo');
    }
};
