<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedimentos', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->unsignedSmallInteger('duracao_minutos')->default(60);
            $table->decimal('valor', 10, 2)->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedimentos');
    }
};
