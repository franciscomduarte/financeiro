<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multiclínica — fase 1: tenant_id em todas as tabelas da clínica.
 * Registros existentes vão para a primeira clínica ("LC Estética").
 */
return new class extends Migration
{
    /** Tabelas vinculadas a uma clínica. */
    public const TABELAS = [
        'agendamentos', 'bloqueios_agenda', 'cobrancas', 'conta_consumo_faturas', 'contas_consumo',
        'contrato_pagamentos', 'contratos', 'contratos_reajustes', 'documento_categorias', 'documento_versoes',
        'documentos', 'fornecedores', 'grade_horarios', 'obrigacao_fiscal_lancamentos', 'obrigacoes_fiscais',
        'pacientes', 'parcelamentos', 'procedimentos', 'profissionais', 'stock_batches', 'stock_categories',
        'stock_movements', 'stock_products', 'taxas_cartao', 'transacao_anexos', 'transacoes',
    ];

    /** Únicos globais que passam a ser únicos dentro da clínica: tabela => coluna. */
    private const UNICOS_POR_CLINICA = [
        'fornecedores'  => 'cnpj',
        'pacientes'     => 'cpf',
        'profissionais' => 'email',
        'taxas_cartao'  => 'modalidade',
    ];

    public function up(): void
    {
        $clinicaId = DB::table('clinicas')->where('slug', 'lc-estetica')->value('id');

        foreach (self::TABELAS as $tabela) {
            Schema::table($tabela, function (Blueprint $table): void {
                $table->uuid('tenant_id')->nullable()->after('id');
            });

            DB::table($tabela)->whereNull('tenant_id')->update(['tenant_id' => $clinicaId]);

            DB::statement("ALTER TABLE {$tabela} ALTER COLUMN tenant_id SET NOT NULL");

            Schema::table($tabela, function (Blueprint $table) use ($tabela): void {
                $table->foreign('tenant_id')->references('id')->on('clinicas')->restrictOnDelete();
                $table->index('tenant_id');
                $table->index(['tenant_id', 'created_at'], "{$tabela}_tenant_created_idx");
            });
        }

        foreach (self::UNICOS_POR_CLINICA as $tabela => $coluna) {
            Schema::table($tabela, function (Blueprint $table) use ($tabela, $coluna): void {
                $table->dropUnique("{$tabela}_{$coluna}_unique");
                $table->unique(['tenant_id', $coluna]);
            });
        }
    }

    public function down(): void
    {
        foreach (self::UNICOS_POR_CLINICA as $tabela => $coluna) {
            Schema::table($tabela, function (Blueprint $table) use ($coluna): void {
                $table->dropUnique(['tenant_id', $coluna]);
                $table->unique($coluna);
            });
        }

        foreach (self::TABELAS as $tabela) {
            Schema::table($tabela, function (Blueprint $table) use ($tabela): void {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex("{$tabela}_tenant_created_idx");
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
