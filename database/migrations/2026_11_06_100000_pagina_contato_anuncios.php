<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Página pública de contato pronta para anúncios: nome público dos procedimentos (o Google reprova nome
 * de medicamento), botão de WhatsApp e tag/conversão do Google Ads por clínica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedimentos', function (Blueprint $table): void {
            $table->string('nome_publico', 100)->nullable()->after('nome');
            $table->boolean('exibir_formulario')->default(true)->after('ativo');
        });

        Schema::table('clinicas', function (Blueprint $table): void {
            $table->string('whatsapp_contato', 30)->nullable();
            $table->string('google_ads_id', 30)->nullable();
            $table->string('google_ads_conversao', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('procedimentos', function (Blueprint $table): void {
            $table->dropColumn(['nome_publico', 'exibir_formulario']);
        });
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->dropColumn(['whatsapp_contato', 'google_ads_id', 'google_ads_conversao']);
        });
    }
};
