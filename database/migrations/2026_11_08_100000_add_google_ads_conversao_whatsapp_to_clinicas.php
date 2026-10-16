<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Conversão do Google Ads no clique do botão "Chamar no WhatsApp" da página de contato. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->string('google_ads_conversao_whatsapp', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->dropColumn('google_ads_conversao_whatsapp');
        });
    }
};
