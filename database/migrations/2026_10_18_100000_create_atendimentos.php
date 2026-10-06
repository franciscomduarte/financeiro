<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de atendimento: fichas configuráveis (anamnese, capilar, facial...), o atendimento com
 * cronômetro e privacidade, injetáveis aplicados (baixa no estoque) e plano de tratamento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas_modelos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->string('nome', 100);
            $table->string('descricao', 255)->nullable();
            $table->jsonb('campos');                       // [{id, tipo, rotulo, opcoes[]}]
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'ativo', 'ordem']);
        });

        Schema::create('atendimentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignUuid('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 15);                  // em_andamento | finalizado
            $table->string('visibilidade', 10);            // privado | equipe
            $table->timestamp('iniciado_em');
            $table->timestamp('finalizado_em')->nullable();
            $table->unsignedInteger('duracao_segundos')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['paciente_id', 'iniciado_em']);
            $table->index('agendamento_id');
        });

        Schema::create('atendimento_fichas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('atendimento_id')->constrained('atendimentos')->cascadeOnDelete();
            $table->foreignUuid('modelo_id')->nullable()->constrained('fichas_modelos')->nullOnDelete();
            $table->string('titulo', 100);                 // nome da ficha no momento do atendimento
            $table->jsonb('campos');                       // cópia dos campos (o modelo pode mudar depois)
            $table->jsonb('respostas')->default('{}');
            $table->timestamps();

            $table->index('tenant_id');
            $table->unique(['atendimento_id', 'modelo_id']);
        });
        DB::statement('CREATE INDEX atendimento_fichas_respostas_gin ON atendimento_fichas USING GIN (respostas)');

        Schema::create('atendimento_injetaveis', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('atendimento_id')->constrained('atendimentos')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('stock_products')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
            $table->decimal('quantidade', 10, 3);
            $table->string('regiao', 120)->nullable();
            $table->string('observacao', 255)->nullable();
            $table->string('lotes_baixados', 255)->nullable(); // lotes usados na baixa (rastreio)
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('atendimento_id');
        });

        Schema::create('planos_tratamento', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('atendimento_id')->nullable()->constrained('atendimentos')->nullOnDelete();
            $table->foreignUuid('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->unique('atendimento_id');
        });

        Schema::create('plano_tratamento_itens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('plano_id')->constrained('planos_tratamento')->cascadeOnDelete();
            $table->foreignId('procedimento_id')->nullable()->constrained('procedimentos')->nullOnDelete();
            $table->string('descricao', 150);
            $table->unsignedSmallInteger('sessoes')->default(1);
            $table->unsignedSmallInteger('intervalo_dias')->nullable();
            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('plano_id');
        });

        Schema::table('prontuario_fotos', function (Blueprint $table): void {
            $table->foreignUuid('atendimento_id')->nullable()->after('agendamento_id')->constrained('atendimentos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prontuario_fotos', fn (Blueprint $table) => $table->dropConstrainedForeignId('atendimento_id'));
        Schema::dropIfExists('plano_tratamento_itens');
        Schema::dropIfExists('planos_tratamento');
        Schema::dropIfExists('atendimento_injetaveis');
        Schema::dropIfExists('atendimento_fichas');
        Schema::dropIfExists('atendimentos');
        Schema::dropIfExists('fichas_modelos');
    }
};
