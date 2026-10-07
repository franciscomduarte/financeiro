<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Contas a pagar geradas pelos contratos (uma por mês), para o contas a pagar e a projeção de caixa. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacoes', function (Blueprint $table): void {
            $table->foreignUuid('contrato_id')->nullable()->after('fornecedor_id')->constrained('contratos')->nullOnDelete();
            $table->index(['tenant_id', 'contrato_id', 'data_competencia']);
        });
    }

    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'contrato_id', 'data_competencia']);
            $table->dropConstrainedForeignId('contrato_id');
        });
    }
};
