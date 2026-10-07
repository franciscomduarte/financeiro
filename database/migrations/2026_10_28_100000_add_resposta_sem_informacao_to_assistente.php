<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Frase que o assistente usa quando a resposta não está no treinamento (e passa para a equipe). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistente_configuracoes', function (Blueprint $table): void {
            $table->string('resposta_sem_informacao', 300)->nullable()->after('instrucoes');
        });
    }

    public function down(): void
    {
        Schema::table('assistente_configuracoes', function (Blueprint $table): void {
            $table->dropColumn('resposta_sem_informacao');
        });
    }
};
