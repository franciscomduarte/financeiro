<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Dados cadastrais do paciente: sexo, estado civil, profissão, endereço e como conheceu a clínica. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            $table->string('sexo', 20)->nullable();
            $table->string('estado_civil', 30)->nullable();
            $table->string('profissao', 100)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->string('origem', 50)->nullable(); // Instagram, Indicação, Facebook...

            $table->index(['tenant_id', 'origem']);
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'origem']);
            $table->dropColumn(['sexo', 'estado_civil', 'profissao', 'endereco', 'origem']);
        });
    }
};
