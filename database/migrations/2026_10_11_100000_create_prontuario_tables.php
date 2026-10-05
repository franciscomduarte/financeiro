<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prontuário eletrônico: evoluções por atendimento, fotos (antes/depois), termos de consentimento
 * assinados na tela e orientações ao paciente, além dos modelos de termo e orientação da clínica.
 * Prontuário não se apaga (guarda mínima de 20 anos, Lei 13.787/2018): as FKs do paciente são restrict.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prontuario_modelos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->string('tipo', 20); // termo | orientacao
            $table->string('titulo', 150);
            $table->text('conteudo');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'tipo']);
        });

        Schema::create('prontuario_evolucoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignUuid('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('texto');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['paciente_id', 'created_at']);
        });

        Schema::create('prontuario_fotos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('momento', 20); // antes | depois | acompanhamento
            $table->string('regiao', 80)->nullable();
            $table->string('descricao', 255)->nullable();
            $table->date('tirada_em');
            $table->string('arquivo_path', 500);
            $table->string('miniatura_path', 500)->nullable();
            $table->string('mime', 50);
            $table->unsignedInteger('tamanho');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['paciente_id', 'tirada_em']);
        });

        Schema::create('prontuario_termos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('modelo_id')->nullable()->constrained('prontuario_modelos')->nullOnDelete();
            $table->foreignUuid('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 150);
            $table->text('conteudo');            // texto final, com os dados do paciente já preenchidos
            $table->string('assinante_nome', 150);
            $table->string('assinatura_path', 500);
            $table->char('hash', 64);            // sha256 do conteúdo + assinatura: prova de que nada mudou
            $table->timestamp('assinado_em');
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['paciente_id', 'assinado_em']);
        });

        Schema::create('prontuario_orientacoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 150);
            $table->text('texto');
            $table->timestamp('enviada_whatsapp_em')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['paciente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prontuario_orientacoes');
        Schema::dropIfExists('prontuario_termos');
        Schema::dropIfExists('prontuario_fotos');
        Schema::dropIfExists('prontuario_evolucoes');
        Schema::dropIfExists('prontuario_modelos');
    }
};
