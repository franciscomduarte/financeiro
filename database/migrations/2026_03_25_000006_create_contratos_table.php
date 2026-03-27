<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('fornecedor_id')->constrained('fornecedores')->restrictOnDelete();

            $table->decimal('valor_mensal', 10, 2);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->string('periodicidade_reajuste', 20)->default('anual');
            $table->date('data_proximo_reajuste')->nullable();
            $table->string('indice_reajuste', 30)->nullable();

            // Multa por rescisão
            $table->decimal('multa_rescisao_valor', 10, 2)->nullable();
            $table->decimal('multa_rescisao_percentual', 5, 2)->nullable();
            $table->integer('aviso_previo_dias')->default(30);

            // Arquivo do contrato (upload direto ou link externo)
            $table->string('arquivo_contrato_path', 500)->nullable();
            $table->string('arquivo_contrato_nome', 255)->nullable();
            $table->text('link_contrato')->nullable();

            $table->string('risco', 10)->default('medio');
            $table->string('status', 20)->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('risco');
            $table->index('data_fim');
            $table->index('data_proximo_reajuste');
            $table->index(['fornecedor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
