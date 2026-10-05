<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Perfis de acesso por clínica: admin, recepção, profissional e financeiro.
 * O antigo "usuário" vira recepção (o admin ajusta quem for profissional ou financeiro).
 * O usuário do sistema pode ser vinculado ao cadastro do profissional (vê só a própria agenda).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('clinica_user')->where('papel', 'user')->update(['papel' => 'recepcao']);
        DB::table('users')->where('role', 'user')->update(['role' => 'recepcao']);

        Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('recepcao')->change());
        Schema::table('clinica_user', fn (Blueprint $table) => $table->string('papel', 20)->default('recepcao')->change());

        Schema::table('profissionais', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('profissionais', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });

        DB::table('clinica_user')->whereIn('papel', ['recepcao', 'profissional', 'financeiro'])->update(['papel' => 'user']);
        DB::table('users')->whereIn('role', ['recepcao', 'profissional', 'financeiro'])->update(['role' => 'user']);

        Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('user')->change());
        Schema::table('clinica_user', fn (Blueprint $table) => $table->string('papel', 20)->default('user')->change());
    }
};
