<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_categorias', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nome', 100);
            $table->string('descricao', 255)->nullable();
            $table->string('cor', 20)->default('slate');
            $table->boolean('requer_validade')->default(true);
            $table->unsignedSmallInteger('alerta_dias_antes')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_categorias');
    }
};
