<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relacionamento com o paciente: intervalo de retorno por procedimento, registro dos contatos
 * feitos pela recepção (retorno, aniversário, paciente sumido) e pesquisa de satisfação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedimentos', function (Blueprint $table): void {
            $table->unsignedSmallInteger('retorno_dias')->nullable();
        });

        Schema::create('relacionamento_contatos', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('tipo', 20);              // retorno | aniversario | sumido
            $table->string('referencia', 64);        // agendamento (retorno), ano (aniversário), mês (sumido)
            $table->string('canal', 20);             // whatsapp | manual
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tenant_id', 'paciente_id', 'tipo', 'referencia']);
            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('pesquisas_satisfacao', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignUuid('agendamento_id')->unique()->constrained('agendamentos')->cascadeOnDelete();
            $table->foreignUuid('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviada_em')->nullable();
            $table->timestamp('respondida_em')->nullable();
            $table->unsignedTinyInteger('nota')->nullable(); // 0 a 10
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'respondida_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesquisas_satisfacao');
        Schema::dropIfExists('relacionamento_contatos');
        Schema::table('procedimentos', fn (Blueprint $table) => $table->dropColumn('retorno_dias'));
    }
};
