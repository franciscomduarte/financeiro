<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Anexos em PDF do prontuário (exames, laudos, receitas), enviados no atendimento. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prontuario_anexos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('atendimento_id')->nullable()->constrained('atendimentos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nome', 150);                 // nome mostrado (descrição ou nome do arquivo)
            $table->string('arquivo_path', 500);
            $table->string('mime', 50);
            $table->unsignedInteger('tamanho');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['paciente_id', 'created_at']);
            $table->index('atendimento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prontuario_anexos');
    }
};
