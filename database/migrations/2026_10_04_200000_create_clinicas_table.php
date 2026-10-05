<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Multiclínica — fase 1: clínicas (tenants) e vínculo usuário ↔ clínica com papel por clínica.
 * A clínica existente ("LC Estética") é criada aqui e recebe todos os usuários atuais.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinicas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nome', 150);
            $table->string('slug', 80)->unique();
            $table->string('status', 20)->default('ativa');
            $table->date('teste_ate')->nullable();
            $table->string('whatsapp_numero', 30)->nullable(); // destino dos alertas e número autorizado a lançar via WhatsApp
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('clinica_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('papel', 20)->default('user'); // RoleUsuario: admin | user
            $table->timestamps();

            $table->unique(['clinica_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            // Dono da plataforma (painel de todas as clínicas — fase 4)
            $table->boolean('is_super_admin')->default(false)->after('active');
        });

        DB::transaction(function (): void {
            $clinicaId = (string) Str::uuid();

            DB::table('clinicas')->insert([
                'id'              => $clinicaId,
                'nome'            => 'LC Estética',
                'slug'            => 'lc-estetica',
                'status'          => 'ativa',
                'whatsapp_numero' => config('services.whatsapp.allowed_number') ?: null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('users')->orderBy('id')->select(['id', 'role'])->chunkById(200, function ($users) use ($clinicaId): void {
                DB::table('clinica_user')->insert($users->map(fn ($u) => [
                    'clinica_id' => $clinicaId,
                    'user_id'    => $u->id,
                    'papel'      => $u->role ?: 'user',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            });
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_super_admin'));
        Schema::dropIfExists('clinica_user');
        Schema::dropIfExists('clinicas');
    }
};
