<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Gestão de leads: interessados ainda não pacientes, funil de vendas e histórico de contatos. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->string('nome', 150);
            $table->string('telefone', 20)->nullable();
            $table->string('telefone_chave', 12)->nullable();          // DDD + 8 últimos dígitos (casa com/sem o 9)
            $table->string('email', 150)->nullable();
            $table->string('origem', 20);
            $table->foreignId('procedimento_id')->nullable()->constrained('procedimentos')->nullOnDelete();
            $table->string('interesse', 255)->nullable();
            $table->string('etapa', 25);
            $table->string('motivo_perda', 150)->nullable();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('proximo_contato_em')->nullable();
            $table->timestamp('primeiro_contato_em')->nullable();      // primeira resposta da clínica
            $table->timestamp('ultima_interacao_em')->nullable();
            $table->foreignUuid('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->timestamp('convertido_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('consentimento_em')->nullable();         // aceite do formulário público (LGPD)
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'etapa']);
            $table->index(['tenant_id', 'telefone_chave']);
            $table->index(['tenant_id', 'proximo_contato_em']);
        });

        Schema::create('lead_interacoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 25);
            $table->text('texto')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tenant_id');
            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_interacoes');
        Schema::dropIfExists('leads');
    }
};
