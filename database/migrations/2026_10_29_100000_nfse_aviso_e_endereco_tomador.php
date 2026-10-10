<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NFS-e: aviso automático da Focus NFe (gatilho) e endereço do tomador.
 * - clinicas: token que a Focus devolve no aviso e quando o gatilho foi cadastrado;
 * - pacientes: endereço em campos separados (o campo livre "endereco" continua para quem já usa);
 * - notas_fiscais: cópia do endereço enviado, quando a nota foi enviada e a última consulta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->text('nfse_webhook_token')->nullable();          // criptografado
            $table->timestamp('nfse_webhook_em')->nullable();
            $table->boolean('nfse_webhook_homologacao')->nullable(); // ambiente em que o gatilho foi cadastrado
        });

        Schema::table('pacientes', function (Blueprint $table): void {
            $table->string('cep', 8)->nullable()->after('endereco');
            $table->string('logradouro', 150)->nullable()->after('cep');
            $table->string('numero', 20)->nullable()->after('logradouro');
            $table->string('complemento', 80)->nullable()->after('numero');
            $table->string('bairro', 80)->nullable()->after('complemento');
            $table->string('cidade', 80)->nullable()->after('bairro');
            $table->string('uf', 2)->nullable()->after('cidade');
            $table->string('codigo_municipio', 7)->nullable()->after('uf'); // IBGE
        });

        Schema::table('notas_fiscais', function (Blueprint $table): void {
            $table->jsonb('tomador_endereco')->nullable()->after('tomador_email');
            $table->date('data_competencia')->nullable()->after('valor');
            $table->timestamp('ultima_consulta_em')->nullable()->after('consultas');
        });
    }

    public function down(): void
    {
        Schema::table('notas_fiscais', function (Blueprint $table): void {
            $table->dropColumn(['tomador_endereco', 'data_competencia', 'ultima_consulta_em']);
        });
        Schema::table('pacientes', function (Blueprint $table): void {
            $table->dropColumn(['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'codigo_municipio']);
        });
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->dropColumn(['nfse_webhook_token', 'nfse_webhook_em', 'nfse_webhook_homologacao']);
        });
    }
};
