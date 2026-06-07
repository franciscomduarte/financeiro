<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_products', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('stock_categories')->restrictOnDelete();
            $table->string('unit_type', 20); // UI, ml, g, mg, unidade, ampola
            $table->decimal('quantity_per_package', 10, 3)->default(1); // qtd por embalagem comprada
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('minimum_stock_quantity', 10, 3)->default(0);
            $table->unsignedSmallInteger('beyond_use_hours')->nullable(); // null = sem restrição
            $table->boolean('requires_lot_control')->default(true);
            $table->uuid('fornecedor_id')->nullable();
            $table->foreign('fornecedor_id')->references('id')->on('fornecedores')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_products');
    }
};
