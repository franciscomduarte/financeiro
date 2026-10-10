<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Confirmação de presença: pedido na véspera (WhatsApp), resposta do paciente e quem confirmou. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->timestamp('confirmacao_pedida_em')->nullable()->after('lembrete_2horas_em');
            $table->timestamp('confirmado_em')->nullable()->after('confirmacao_pedida_em');
            $table->string('confirmacao_origem', 20)->nullable()->after('confirmado_em');
            $table->timestamp('pediu_remarcar_em')->nullable()->after('confirmacao_origem');
            $table->text('resposta_confirmacao')->nullable()->after('pediu_remarcar_em');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropColumn(['confirmacao_pedida_em', 'confirmado_em', 'confirmacao_origem', 'pediu_remarcar_em', 'resposta_confirmacao']);
        });
    }
};
