<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('stock_batches')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('stock_products')->restrictOnDelete();
            $table->string('type', 30); // purchase, procedure_use, manual_exit, discard_expired, adjustment
            $table->decimal('quantity', 10, 3); // positivo = entrada, negativo = saída
            $table->decimal('unit_cost_at_time', 10, 2)->nullable();
            $table->uuid('paciente_id')->nullable(); // nullable FK (UUID) → pacientes
            $table->string('procedure_name', 200)->nullable(); // livre até ter model Procedimento
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']);
            $table->index(['batch_id', 'created_at']);
            $table->index('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
