<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_horarios', function (Blueprint $table): void {
            $table->id();
            $table->uuid('profissional_id');
            $table->foreign('profissional_id')->references('id')->on('profissionais')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 0=domingo, 6=sábado
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['profissional_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_horarios');
    }
};
