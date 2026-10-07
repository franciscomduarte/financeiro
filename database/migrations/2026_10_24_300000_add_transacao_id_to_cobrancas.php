<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cobrança do Asaas ligada à sua conta a receber (o pagamento dá baixa sozinho). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobrancas', function (Blueprint $table): void {
            $table->foreignUuid('transacao_id')->nullable()->after('parcelamento_id')->constrained('transacoes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cobrancas', fn (Blueprint $table) => $table->dropConstrainedForeignId('transacao_id'));
    }
};
