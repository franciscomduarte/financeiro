<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table): void {
            $table->string('periodicidade_reajuste', 20)->nullable()->default(null)->change();
            $table->integer('aviso_previo_dias')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table): void {
            $table->string('periodicidade_reajuste', 20)->default('anual')->change();
            $table->integer('aviso_previo_dias')->default(30)->change();
        });
    }
};
