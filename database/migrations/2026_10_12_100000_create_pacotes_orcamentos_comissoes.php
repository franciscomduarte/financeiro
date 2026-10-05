<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pacotes de sessões (vendidos ao paciente, com saldo consumido nos atendimentos),
 * orçamentos (que, aprovados, viram receita e pacotes) e comissões dos profissionais
 * (percentual sobre o recebido, com fechamento mensal lançado como despesa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('aberto'); // aberto | aprovado | recusado
            $table->date('validade');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('observacoes')->nullable();
            $table->string('forma_pagamento', 30)->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('decidido_em')->nullable();
            $table->uuid('transacao_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'numero']);
            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status']);
            $table->index('paciente_id');
        });

        Schema::create('orcamento_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();
            $table->foreignId('procedimento_id')->nullable()->constrained('procedimentos')->nullOnDelete();
            $table->string('descricao', 150);
            $table->unsignedSmallInteger('quantidade');
            $table->decimal('valor_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);

            $table->index('tenant_id');
            $table->index('orcamento_id');
        });

        Schema::create('pacotes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('procedimento_id')->nullable()->constrained('procedimentos')->nullOnDelete();
            $table->foreignUuid('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
            $table->foreignUuid('transacao_id')->nullable()->constrained('transacoes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nome', 150);
            $table->unsignedSmallInteger('sessoes_total');
            $table->unsignedSmallInteger('sessoes_usadas')->default(0);
            $table->decimal('valor_total', 10, 2);
            $table->date('validade')->nullable();
            $table->string('status', 20)->default('ativo'); // ativo | concluido | cancelado
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status']);
            $table->index(['paciente_id', 'status']);
        });

        Schema::create('pacote_sessoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('pacote_id')->constrained('pacotes')->cascadeOnDelete();
            $table->foreignUuid('agendamento_id')->constrained('agendamentos')->restrictOnDelete();
            $table->foreignUuid('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->date('usada_em');
            $table->timestamps();

            $table->unique('agendamento_id'); // um atendimento usa no máximo uma sessão
            $table->index('tenant_id');
            $table->index(['tenant_id', 'usada_em']);
            $table->index('pacote_id');
        });

        Schema::table('profissionais', function (Blueprint $table): void {
            $table->decimal('comissao_percentual', 5, 2)->default(0);
        });

        Schema::create('comissao_fechamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('profissional_id')->constrained('profissionais')->restrictOnDelete();
            $table->char('competencia', 7); // AAAA-MM
            $table->decimal('base', 10, 2);
            $table->decimal('percentual', 5, 2);
            $table->decimal('valor', 10, 2);
            $table->foreignUuid('transacao_id')->nullable()->constrained('transacoes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'profissional_id', 'competencia']);
            $table->index('tenant_id');
            $table->index(['tenant_id', 'competencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comissao_fechamentos');
        Schema::table('profissionais', fn (Blueprint $table) => $table->dropColumn('comissao_percentual'));
        Schema::dropIfExists('pacote_sessoes');
        Schema::dropIfExists('pacotes');
        Schema::dropIfExists('orcamento_itens');
        Schema::dropIfExists('orcamentos');
    }
};
