<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos_reajustes', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('contrato_id')->constrained('contratos')->cascadeOnDelete();

            $table->date('data_reajuste');
            $table->decimal('valor_anterior', 10, 2);
            $table->decimal('valor_novo', 10, 2);
            $table->string('indice', 30)->nullable();
            $table->decimal('percentual_efetivo', 5, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['contrato_id', 'data_reajuste']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos_reajustes');
    }
};
