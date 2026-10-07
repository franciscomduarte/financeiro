<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversa do lead no WhatsApp: id da mensagem na Evolution, para não registrar duas vezes
 * a mesma mensagem (reenvio do webhook ou eco da resposta enviada pelo sistema).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_interacoes', function (Blueprint $table): void {
            $table->string('mensagem_id', 100)->nullable()->after('texto');
            $table->unique(['tenant_id', 'mensagem_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lead_interacoes', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'mensagem_id']);
            $table->dropColumn('mensagem_id');
        });
    }
};
