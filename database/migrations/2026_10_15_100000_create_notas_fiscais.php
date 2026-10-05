<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NFS-e pela Focus NFe: dados fiscais da clínica (prestador) e as notas emitidas a partir
 * das receitas em Lançamentos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->text('nfse_token')->nullable();                       // token da Focus NFe (criptografado)
            $table->boolean('nfse_homologacao')->default(true);           // ambiente de testes até a clínica liberar
            $table->string('inscricao_municipal', 30)->nullable();
            $table->string('codigo_municipio', 7)->nullable();            // IBGE, ex.: 5300108 (Brasília)
            $table->string('nfse_item_lista_servico', 10)->nullable();    // LC 116, ex.: 06.02
            $table->string('nfse_codigo_tributario', 30)->nullable();     // código de tributação do município
            $table->decimal('nfse_aliquota_iss', 5, 2)->nullable();       // %
            $table->boolean('nfse_optante_simples')->default(true);
            $table->text('nfse_discriminacao_padrao')->nullable();
        });

        Schema::create('notas_fiscais', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->foreignUuid('transacao_id')->constrained('transacoes')->restrictOnDelete();
            $table->foreignUuid('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referencia', 50)->unique();      // "ref" enviada à Focus (idempotência)
            $table->string('status', 20);                    // processando | autorizada | erro | cancelada
            $table->boolean('homologacao');
            $table->decimal('valor', 10, 2);
            $table->text('discriminacao');
            $table->string('tomador_nome', 150);
            $table->string('tomador_cpf', 14)->nullable();
            $table->string('tomador_email', 150)->nullable();
            $table->string('numero', 30)->nullable();
            $table->string('codigo_verificacao', 60)->nullable();
            $table->string('url', 500)->nullable();          // DANFSE / consulta na prefeitura
            $table->string('url_xml', 500)->nullable();
            $table->text('mensagem_erro')->nullable();
            $table->unsignedSmallInteger('consultas')->default(0);
            $table->timestamp('autorizada_em')->nullable();
            $table->timestamp('cancelada_em')->nullable();
            $table->string('justificativa_cancelamento', 255)->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status']);
            $table->index('transacao_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais');
        Schema::table('clinicas', fn (Blueprint $table) => $table->dropColumn([
            'nfse_token', 'nfse_homologacao', 'inscricao_municipal', 'codigo_municipio', 'nfse_item_lista_servico',
            'nfse_codigo_tributario', 'nfse_aliquota_iss', 'nfse_optante_simples', 'nfse_discriminacao_padrao',
        ]));
    }
};
