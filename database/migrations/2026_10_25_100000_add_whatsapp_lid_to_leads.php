<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp passou a identificar parte dos contatos por um código (LID, "...@lid") em vez do número.
 * Sem o número, o lead é reconhecido por esse código.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('whatsapp_lid', 40)->nullable()->after('telefone_chave');
            $table->index(['tenant_id', 'whatsapp_lid']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'whatsapp_lid']);
            $table->dropColumn('whatsapp_lid');
        });
    }
};
