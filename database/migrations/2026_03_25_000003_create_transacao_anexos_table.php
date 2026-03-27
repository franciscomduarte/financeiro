<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacao_anexos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('transacao_id');
            $table->string('tipo', 20);
            $table->string('nome_arquivo', 255);
            $table->string('caminho', 500);
            $table->string('mime_type', 100)->nullable();
            $table->integer('tamanho_bytes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('transacao_id')
                ->references('id')
                ->on('transacoes')
                ->cascadeOnDelete();

            $table->index('transacao_id');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacao_anexos');
    }
};
