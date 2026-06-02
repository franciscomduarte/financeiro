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
            $table->uuid('paciente_id')->nullable()->after('cliente');

            $table->foreign('paciente_id')
                ->references('id')
                ->on('pacientes')
                ->nullOnDelete();

            $table->index('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table): void {
            $table->dropForeign(['paciente_id']);
            $table->dropIndex(['paciente_id']);
            $table->dropColumn('paciente_id');
        });
    }
};
