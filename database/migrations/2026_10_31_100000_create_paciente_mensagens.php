<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Conversa no WhatsApp com quem já é paciente (fica na ficha, fora do funil de leads). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paciente_mensagens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // quem respondeu pelo sistema
            $table->boolean('enviada');                     // true: da clínica · false: do paciente
            $table->text('texto');
            $table->string('mensagem_id', 100)->nullable(); // id da Evolution (evita duplicar o eco do webhook)
            $table->timestamp('lida_em')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['paciente_id', 'created_at']);
            $table->unique(['tenant_id', 'mensagem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_mensagens');
    }
};
