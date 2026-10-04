<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bloqueios_agenda', function (Blueprint $table): void {
            // Bloqueios criados juntos ("todos os profissionais") compartilham o grupo.
            $table->uuid('grupo_id')->nullable()->after('profissional_id');
            $table->boolean('dia_inteiro')->default(false)->after('fim_em');

            $table->index('grupo_id');
            $table->index(['inicio_em', 'fim_em']);
        });
    }

    public function down(): void
    {
        Schema::table('bloqueios_agenda', function (Blueprint $table): void {
            $table->dropIndex(['inicio_em', 'fim_em']);
            $table->dropIndex(['grupo_id']);
            $table->dropColumn(['grupo_id', 'dia_inteiro']);
        });
    }
};
