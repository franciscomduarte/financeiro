<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxas_cartao', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('modalidade', 50)->unique();
            $table->decimal('percentual', 5, 2);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxas_cartao');
    }
};
