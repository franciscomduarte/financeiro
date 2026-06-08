<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueios_agenda', function (Blueprint $table): void {
            $table->id();
            $table->uuid('profissional_id');
            $table->foreign('profissional_id')->references('id')->on('profissionais')->cascadeOnDelete();
            $table->timestamp('inicio_em');
            $table->timestamp('fim_em');
            $table->string('motivo', 500)->nullable();
            $table->timestamps();

            $table->index(['profissional_id', 'inicio_em', 'fim_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueios_agenda');
    }
};
