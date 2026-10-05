<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multiclínica — fase 4 (painel do dono da plataforma): último acesso e data de ativação da
 * clínica, e histórico de eventos (cadastro, ativação, bloqueio, extensão de teste, suporte).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->timestamp('ultimo_acesso_em')->nullable();
            $table->timestamp('ativada_em')->nullable();

            $table->index('created_at');
        });

        // Clínicas que já estão ativas contam como ativadas desde o cadastro
        DB::table('clinicas')->where('status', 'ativa')->update(['ativada_em' => DB::raw('created_at')]);

        // Tabela da plataforma (não pertence a uma clínica): sem tenant_id/escopo
        Schema::create('clinica_eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 40);
            $table->jsonb('detalhes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['clinica_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinica_eventos');

        Schema::table('clinicas', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropColumn(['ultimo_acesso_em', 'ativada_em']);
        });
    }
};
