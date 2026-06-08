<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profissionais', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nome', 150);
            $table->string('email', 200)->unique();
            $table->string('telefone', 20)->nullable();
            $table->string('google_calendar_id')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->string('cor_agenda', 7)->default('#be123c');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profissionais');
    }
};
