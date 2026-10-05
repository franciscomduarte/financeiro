<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multiclínica — fase 3: controle dos avisos de fim do teste grátis (cada aviso sai uma única vez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->timestamp('aviso_teste_3_dias_em')->nullable();
            $table->timestamp('aviso_teste_fim_em')->nullable();

            // Rotina diária de avisos filtra clínicas em teste pela data de término
            $table->index(['status', 'teste_ate']);
        });
    }

    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->dropIndex(['status', 'teste_ate']);
            $table->dropColumn(['aviso_teste_3_dias_em', 'aviso_teste_fim_em']);
        });
    }
};
