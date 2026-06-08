<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            // Armazena todos os IDs de procedimentos selecionados (o primeiro é o principal)
            $table->jsonb('procedimentos_ids')->nullable()->after('procedimento_id');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropColumn('procedimentos_ids');
        });
    }
};
