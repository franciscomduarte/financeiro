<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multiclínica — fase 2: configurações por clínica (identidade, imposto e integrações).
 * Chaves de integração ficam criptografadas (APP_KEY). A LC Estética herda os valores do .env atual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            // Identidade (menu, e-mails e mensagens aos pacientes)
            $table->string('razao_social', 200)->nullable()->after('nome');
            $table->string('cnpj', 20)->nullable()->after('razao_social');
            $table->string('telefone', 30)->nullable()->after('cnpj');
            $table->string('email_contato', 150)->nullable()->after('telefone');
            $table->string('endereco', 300)->nullable()->after('email_contato');
            $table->string('slogan', 150)->nullable()->after('endereco');
            $table->string('logo_path', 300)->nullable()->after('slogan');

            // Financeiro
            $table->decimal('aliquota_imposto', 5, 2)->default(6.00);

            // WhatsApp (Evolution: servidor único, uma instância por clínica)
            $table->string('evolution_instance', 100)->nullable();
            $table->text('evolution_api_key')->nullable();

            // Asaas (conta própria da clínica)
            $table->text('asaas_api_key')->nullable();
            $table->boolean('asaas_sandbox')->default(false);
            $table->text('asaas_webhook_token')->nullable();
        });

        $cifrar = fn (?string $v) => filled($v) ? Crypt::encryptString($v) : null;

        DB::table('clinicas')->where('slug', 'lc-estetica')->update([
            'slogan'              => 'Saúde Integrativa',
            'evolution_instance'  => config('evolution.instance') ?: null,
            'evolution_api_key'   => $cifrar(config('evolution.api_key')),
            'asaas_api_key'       => $cifrar(config('asaas.api_key')),
            'asaas_sandbox'       => (bool) config('asaas.sandbox'),
            'asaas_webhook_token' => $cifrar(config('asaas.webhook_token')),
            'updated_at'          => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->dropColumn([
                'razao_social', 'cnpj', 'telefone', 'email_contato', 'endereco', 'slogan', 'logo_path',
                'aliquota_imposto', 'evolution_instance', 'evolution_api_key',
                'asaas_api_key', 'asaas_sandbox', 'asaas_webhook_token',
            ]);
        });
    }
};
