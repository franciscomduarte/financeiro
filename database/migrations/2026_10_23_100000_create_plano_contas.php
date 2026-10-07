<?php

declare(strict_types=1);

use App\Models\PlanoConta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Financeiro, fase 2: plano de contas em grupos da DRE. Cada clínica recebe o plano padrão; categorias
 * já usadas que não estão nele viram contas (receitas em "Outras receitas", despesas em
 * "Despesas administrativas") e todo lançamento passa a apontar para a sua conta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_contas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('clinicas')->restrictOnDelete();
            $table->string('nome', 100);
            $table->string('tipo', 10);              // entrada ou saida
            $table->string('grupo', 30);             // GrupoPlanoContas
            $table->boolean('ativa')->default(true);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'created_at']);
            $table->unique(['tenant_id', 'tipo', 'nome']);
        });

        Schema::table('transacoes', function (Blueprint $table): void {
            $table->foreignUuid('categoria_id')->nullable()->after('categoria')->constrained('plano_contas')->restrictOnDelete();
            $table->index(['tenant_id', 'categoria_id']);
        });

        DB::transaction(function (): void {
            $agora = now();
            foreach (DB::table('clinicas')->pluck('id') as $clinicaId) {
                $existentes = [];
                foreach (PlanoConta::PADRAO as $tipo => $contas) {
                    $ordem = 0;
                    foreach ($contas as $nome => $grupo) {
                        DB::table('plano_contas')->insert([
                            'id' => (string) Str::uuid(), 'tenant_id' => $clinicaId, 'nome' => $nome, 'tipo' => $tipo, 'grupo' => $grupo,
                            'ativa' => true, 'ordem' => $ordem += 10, 'created_at' => $agora, 'updated_at' => $agora,
                        ]);
                        $existentes[$tipo][mb_strtolower($nome)] = true;
                    }
                }

                // Categorias já usadas fora do plano viram contas
                $usadas = DB::table('transacoes')->where('tenant_id', $clinicaId)->select(['tipo', 'categoria'])->distinct()->get();
                foreach ($usadas as $u) {
                    $nome = trim((string) $u->categoria);
                    if ($nome === '' || isset($existentes[$u->tipo][mb_strtolower($nome)]) || in_array(mb_strtolower($nome), ['servicos', 'serviços'], true)) {
                        continue;
                    }
                    DB::table('plano_contas')->insert([
                        'id' => (string) Str::uuid(), 'tenant_id' => $clinicaId, 'nome' => mb_substr($nome, 0, 100), 'tipo' => $u->tipo,
                        'grupo' => $u->tipo === 'entrada' ? 'outras_receitas' : 'administrativas',
                        'ativa' => true, 'ordem' => 900, 'created_at' => $agora, 'updated_at' => $agora,
                    ]);
                    $existentes[$u->tipo][mb_strtolower($nome)] = true;
                }
            }

            // Liga cada lançamento à sua conta (comissões e o antigo "servicos" têm conta própria)
            DB::statement("
                UPDATE transacoes t SET categoria_id = p.id
                FROM plano_contas p
                WHERE p.tenant_id = t.tenant_id AND p.tipo = t.tipo AND LOWER(p.nome) = LOWER(
                    CASE
                        WHEN t.tipo = 'saida' AND LOWER(COALESCE(t.subcategoria, '')) = 'comissões' THEN 'Comissões'
                        WHEN t.tipo = 'saida' AND LOWER(t.categoria) IN ('servicos', 'serviços') THEN 'Serviços de terceiros'
                        ELSE TRIM(t.categoria)
                    END)
            ");
            DB::statement("
                UPDATE transacoes t SET categoria_id = p.id
                FROM plano_contas p
                WHERE t.categoria_id IS NULL AND p.tenant_id = t.tenant_id AND p.tipo = t.tipo AND p.nome = 'Outros'
            ");
        });
    }

    public function down(): void
    {
        Schema::table('transacoes', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'categoria_id']);
            $table->dropConstrainedForeignId('categoria_id');
        });
        Schema::dropIfExists('plano_contas');
    }
};
