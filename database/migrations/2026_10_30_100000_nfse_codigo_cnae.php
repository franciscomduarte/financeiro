<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** NFS-e: código CNAE do serviço (exigido por algumas prefeituras, como Brasília/IssNet). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->string('nfse_codigo_cnae', 7)->nullable()->after('nfse_codigo_tributario');
        });
    }

    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->dropColumn('nfse_codigo_cnae');
        });
    }
};
