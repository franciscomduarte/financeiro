<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobrancas', function (Blueprint $table): void {
            $table->foreignId('parcelamento_id')
                ->nullable()
                ->after('paciente_id')
                ->constrained('parcelamentos')
                ->nullOnDelete();

            $table->unsignedSmallInteger('numero_parcela')->nullable()->after('parcelamento_id');

            $table->index(['parcelamento_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('cobrancas', function (Blueprint $table): void {
            $table->dropForeignIdFor(\App\Models\Parcelamento::class);
            $table->dropColumn(['parcelamento_id', 'numero_parcela']);
        });
    }
};
