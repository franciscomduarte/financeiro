<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('paciente_id');
            $table->foreign('paciente_id')->references('id')->on('pacientes')->restrictOnDelete();
            $table->uuid('profissional_id');
            $table->foreign('profissional_id')->references('id')->on('profissionais')->restrictOnDelete();
            $table->foreignId('procedimento_id')->constrained('procedimentos')->restrictOnDelete();
            $table->timestamp('inicio_em');
            $table->timestamp('fim_em');
            $table->string('status', 20)->default('agendado');
            $table->text('observacoes')->nullable();
            $table->string('motivo_cancelamento', 500)->nullable();
            $table->uuid('agendamento_origem_id')->nullable()->index();
            $table->string('google_event_id')->nullable();
            $table->timestamp('whatsapp_enviado_em')->nullable();
            $table->timestamp('email_enviado_em')->nullable();
            $table->timestamp('lembrete_1dia_em')->nullable();
            $table->timestamp('lembrete_2horas_em')->nullable();
            $table->timestamps();

            $table->index(['profissional_id', 'inicio_em']);
            $table->index(['paciente_id']);
            $table->index(['status']);
        });

        // FK auto-referencial adicionada após a tabela existir (PostgreSQL exige PK criada primeiro)
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->foreign('agendamento_origem_id')->references('id')->on('agendamentos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
