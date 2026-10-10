<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** RG do paciente (pedido no cadastro, inclusive pelo assistente do WhatsApp). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            $table->string('rg', 20)->nullable()->after('cpf');
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            $table->dropColumn('rg');
        });
    }
};
