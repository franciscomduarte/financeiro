<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NFS-e: escolha entre o padrão da prefeitura (/v2/nfse) e o padrão nacional (/v2/nfsen) da Focus NFe.
 * A nota guarda o padrão com que foi enviada para consultar e cancelar no mesmo endereço.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->string('nfse_padrao', 10)->default('municipal');                // municipal | nacional
            $table->string('nfse_codigo_tributacao_nacional', 6)->nullable();       // cTribNac, ex.: 060201
        });

        Schema::table('notas_fiscais', function (Blueprint $table): void {
            $table->string('padrao', 10)->default('municipal');
        });
    }

    public function down(): void
    {
        Schema::table('notas_fiscais', fn (Blueprint $table) => $table->dropColumn('padrao'));
        Schema::table('clinicas', fn (Blueprint $table) => $table->dropColumn(['nfse_padrao', 'nfse_codigo_tributacao_nacional']));
    }
};
