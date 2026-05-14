<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_pagamentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->string('competencia', 7); // YYYY-MM
            $table->decimal('valor', 10, 2);
            $table->date('data_pagamento');
            $table->string('forma_pagamento');
            $table->foreignUuid('transacao_id')->nullable()->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['contrato_id', 'competencia']);
            $table->index('contrato_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_pagamentos');
    }
};
