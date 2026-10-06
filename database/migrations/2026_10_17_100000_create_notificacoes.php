<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central de notificações: tudo o que a clínica manda ao paciente (WhatsApp e e-mail),
 * enviado ou agendado, com o status de entrega. E os textos/configurações por tipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->string('tipo', 40);
            $table->string('canal', 10);                       // whatsapp | email
            $table->string('status', 12);                      // agendada | enviando | enviada | entregue | lida | falhou | cancelada
            $table->string('destinatario_nome', 150);
            $table->string('destino', 150)->nullable();        // telefone ou e-mail usado no envio
            $table->string('assunto', 200);
            $table->text('conteudo')->nullable();
            $table->string('origem_type', 100)->nullable();     // agendamento, cobrança, orçamento...
            $table->string('origem_id', 40)->nullable();        // texto: as origens têm ids uuid e numéricos
            $table->timestamp('agendada_para')->nullable();
            $table->timestamp('enviada_em')->nullable();
            $table->timestamp('entregue_em')->nullable();
            $table->timestamp('lida_em')->nullable();
            $table->string('id_externo', 191)->nullable();     // id da mensagem no WhatsApp
            $table->text('erro')->nullable();
            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status', 'agendada_para']);
            $table->index('id_externo');
            $table->index(['origem_type', 'origem_id']);
        });

        Schema::create('notificacao_configuracoes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->string('tipo', 40);
            $table->boolean('whatsapp')->default(true);
            $table->boolean('email')->default(true);
            $table->unsignedInteger('antecedencia_minutos')->nullable(); // lembretes
            $table->string('assunto', 200)->nullable();                  // null = texto padrão
            $table->text('texto')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->unique(['tenant_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacao_configuracoes');
        Schema::dropIfExists('notificacoes');
    }
};
