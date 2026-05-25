<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conta_consumo_faturas', function (Blueprint $table): void {
            $table->string('arquivo_nome')->nullable()->after('arquivo_path');
        });
    }

    public function down(): void
    {
        Schema::table('conta_consumo_faturas', function (Blueprint $table): void {
            $table->dropColumn('arquivo_nome');
        });
    }
};
