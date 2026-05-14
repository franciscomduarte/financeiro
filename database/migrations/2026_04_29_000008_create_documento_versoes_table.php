<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_versoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->string('numero_documento', 100)->nullable();
            $table->date('data_emissao')->nullable();
            $table->date('data_validade')->nullable();
            $table->string('arquivo_path', 500)->nullable();
            $table->string('arquivo_nome', 255)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('documento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_versoes');
    }
};
