<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obrigacoes_fiscais', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tipo_tributo');
            $table->string('descricao');
            $table->string('codigo_receita', 10)->nullable();
            $table->string('periodicidade');
            $table->unsignedTinyInteger('dia_vencimento')->nullable();
            $table->string('status')->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('tipo_tributo');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obrigacoes_fiscais');
    }
};
