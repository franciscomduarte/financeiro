<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcelamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('descricao', 200);
            $table->decimal('valor_total', 10, 2);
            $table->decimal('valor_parcela', 10, 2);
            $table->unsignedSmallInteger('total_parcelas');
            $table->unsignedTinyInteger('dia_cobranca'); // 1-28: dia do mês para disparo automático
            $table->date('data_inicio');
            $table->enum('status', ['ativo', 'concluido', 'cancelado'])->default('ativo');
            $table->timestamps();

            $table->index(['status', 'dia_cobranca']);
            $table->index('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelamentos');
    }
};
