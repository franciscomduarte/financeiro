<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Campanhas de WhatsApp para pacientes: mensagem, imagem opcional e destinatários enviados em ritmo controlado. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanhas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('nome', 120);
            $table->text('mensagem');
            $table->string('imagem_path')->nullable();
            $table->string('imagem_mime', 50)->nullable();
            $table->string('status', 20)->default('rascunho');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('enviados')->default(0);
            $table->unsignedInteger('falhas')->default(0);
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('campanha_destinatarios', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->foreignUuid('campanha_id')->constrained('campanhas')->cascadeOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('status', 20)->default('pendente');
            $table->timestamp('enviado_em')->nullable();
            $table->string('mensagem_id', 100)->nullable();
            $table->string('erro', 300)->nullable();
            $table->timestamps();
            $table->unique(['campanha_id', 'paciente_id']);
            $table->index(['campanha_id', 'status']);
            $table->index(['tenant_id', 'enviado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campanha_destinatarios');
        Schema::dropIfExists('campanhas');
    }
};
