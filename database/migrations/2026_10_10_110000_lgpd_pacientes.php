<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LGPD: consentimento do paciente (uso dos dados e mensagens de divulgação), anonimização
 * e registro de quem acessou ou alterou os dados de cada paciente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            $table->timestamp('consentimento_em')->nullable();
            $table->foreignId('consentimento_por')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('aceita_whatsapp_marketing')->default(false);
            $table->boolean('aceita_email_marketing')->default(false);
            $table->timestamp('anonimizado_em')->nullable();
        });

        Schema::create('paciente_acessos', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 30);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['paciente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_acessos');

        Schema::table('pacientes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('consentimento_por');
            $table->dropColumn(['consentimento_em', 'aceita_whatsapp_marketing', 'aceita_email_marketing', 'anonimizado_em']);
        });
    }
};
