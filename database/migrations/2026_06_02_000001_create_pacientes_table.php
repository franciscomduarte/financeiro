<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->string('nome', 150);
            $table->string('cpf', 14)->nullable()->unique();
            $table->date('data_nascimento')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('foto_path', 500)->nullable();
            $table->text('anamnese')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('status', 20)->default('ativo');

            $table->timestamps();

            $table->index('status');
            $table->index('cpf');
            $table->index(['nome', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
