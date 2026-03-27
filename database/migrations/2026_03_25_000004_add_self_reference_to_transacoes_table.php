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
            $table->foreign('transacao_pai_id')
                ->references('id')
                ->on('transacoes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table): void {
            $table->dropForeign(['transacao_pai_id']);
        });
    }
};
