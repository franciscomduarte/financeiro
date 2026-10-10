<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Marca a resposta automática do assistente na conversa do paciente. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paciente_mensagens', function (Blueprint $table): void {
            $table->boolean('do_assistente')->default(false)->after('enviada');
        });
    }

    public function down(): void
    {
        Schema::table('paciente_mensagens', function (Blueprint $table): void {
            $table->dropColumn('do_assistente');
        });
    }
};
