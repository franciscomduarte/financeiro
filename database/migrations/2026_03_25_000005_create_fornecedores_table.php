<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome_fantasia', 150);
            $table->string('razao_social', 200)->nullable();
            $table->string('cnpj', 20)->nullable()->unique();
            $table->string('servico_prestado', 200);
            $table->string('categoria', 100)->nullable();

            // Contato principal
            $table->string('contato_nome', 150)->nullable();
            $table->string('contato_telefone', 20)->nullable();
            $table->string('contato_email', 150)->nullable();

            // Contato emergência
            $table->string('contato_emergencia_nome', 150)->nullable();
            $table->string('contato_emergencia_telefone', 20)->nullable();

            $table->string('status', 20)->default('ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedores');
    }
};
