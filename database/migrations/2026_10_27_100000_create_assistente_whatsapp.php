<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assistente virtual que responde os leads no WhatsApp com base no treinamento cadastrado pela clínica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistente_configuracoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->unique()->constrained('clinicas')->restrictOnDelete();
            $table->boolean('ativo')->default(false);
            $table->string('nome', 60)->default('Assistente');
            $table->text('instrucoes')->nullable();          // tom de voz e regras próprias da clínica
            $table->boolean('informar_precos')->default(true);
            $table->boolean('pode_agendar')->default(true);
            $table->unsignedBigInteger('procedimento_avaliacao_id')->nullable();
            $table->foreignUuid('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->unsignedInteger('limite_respostas_mes')->default(500);
            $table->unsignedInteger('respostas_mes')->default(0);
            $table->date('mes_referencia')->nullable();
            $table->timestamps();

            $table->foreign('procedimento_avaliacao_id')->references('id')->on('procedimentos')->nullOnDelete();
        });

        Schema::create('assistente_conhecimentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->string('titulo', 120);
            $table->text('conteudo');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('assistente_pausado_em')->nullable()->after('consentimento_em');
            $table->string('assistente_motivo', 200)->nullable()->after('assistente_pausado_em');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn(['assistente_pausado_em', 'assistente_motivo']);
        });
        Schema::dropIfExists('assistente_conhecimentos');
        Schema::dropIfExists('assistente_configuracoes');
    }
};
