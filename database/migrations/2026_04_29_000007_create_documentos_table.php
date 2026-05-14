<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('categoria_id')->constrained('documento_categorias')->restrictOnDelete();
            $table->string('titulo', 200);
            $table->string('numero_documento', 100)->nullable();
            $table->string('orgao_emissor', 150)->nullable();
            $table->string('responsavel', 150)->nullable();
            $table->date('data_emissao')->nullable();
            $table->date('data_validade')->nullable();
            $table->unsignedSmallInteger('alerta_dias_antes')->nullable();
            $table->string('status', 20)->default('vigente');
            $table->string('arquivo_path', 500)->nullable();
            $table->string('arquivo_nome', 255)->nullable();
            $table->unsignedInteger('arquivo_tamanho_kb')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['status', 'data_validade']);
            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
