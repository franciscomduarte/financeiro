<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('stock_products')->restrictOnDelete();
            $table->string('lot_number', 100)->nullable();
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at'); // validade do fabricante
            $table->decimal('quantity_total', 10, 3); // quantidade comprada
            $table->decimal('quantity_available', 10, 3); // saldo atual
            $table->decimal('purchase_cost', 10, 2)->default(0); // custo desta compra
            $table->date('purchased_at');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('beyond_use_expires_at')->nullable(); // calculado ao abrir
            $table->string('status', 20)->default('sealed'); // sealed, open, empty, expired, discarded
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'beyond_use_expires_at']);
            $table->index(['product_id', 'expires_at']); // FEFO ordering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
