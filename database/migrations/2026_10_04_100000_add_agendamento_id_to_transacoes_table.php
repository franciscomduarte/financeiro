<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacoes', function (Blueprint $table): void {
            // Receita gerada ao concluir um atendimento da agenda (no máximo uma por agendamento).
            $table->uuid('agendamento_id')->nullable()->after('paciente_id');
            $table->foreign('agendamento_id')->references('id')->on('agendamentos')->nullOnDelete();
            $table->unique('agendamento_id');
        });
    }

    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table): void {
            $table->dropForeign(['agendamento_id']);
            $table->dropUnique(['agendamento_id']);
            $table->dropColumn('agendamento_id');
        });
    }
};
