<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GrupoPlanoContas;
use App\Enums\TipoTransacao;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Categoria de receita ou despesa, dentro de um grupo da DRE. */
class PlanoConta extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'plano_contas';

    protected $fillable = ['nome', 'grupo', 'tipo', 'ativa', 'ordem'];

    protected $casts = [
        'grupo' => GrupoPlanoContas::class,
        'tipo'  => TipoTransacao::class,
        'ativa' => 'boolean',
        'ordem' => 'integer',
    ];

    /** Plano inicial de uma clínica de estética (nome => grupo). Os nomes antigos foram mantidos. */
    public const PADRAO = [
        'entrada' => [
            'Procedimento Facial'           => 'receita_servicos',
            'Procedimento Corporal'         => 'receita_servicos',
            'Depilação'                     => 'receita_servicos',
            'Massagem'                      => 'receita_servicos',
            'Skincare'                      => 'receita_servicos',
            'Consultas e avaliações'        => 'receita_servicos',
            'Produto Vendido'               => 'receita_produtos',
            'Outros'                        => 'outras_receitas',
            'Rendimentos e juros recebidos' => 'receitas_financeiras',
            'Aporte dos sócios'             => 'aportes',
            'Empréstimo recebido'           => 'aportes',
        ],
        'saida' => [
            'Simples Nacional'              => 'deducoes',
            'Impostos'                      => 'deducoes',
            'Impostos Municipais'           => 'deducoes',
            'Impostos Federais'             => 'deducoes',
            'Insumos'                       => 'custos_variaveis',
            'Comissões'                     => 'custos_variaveis',
            'Produtos para revenda'         => 'custos_variaveis',
            'Pessoal'                       => 'pessoal',
            'Encargos Sociais'              => 'pessoal',
            'Pró-labore'                    => 'pessoal',
            'Aluguel e condomínio'          => 'ocupacao',
            'Infraestrutura'                => 'ocupacao',
            'Utilidades'                    => 'ocupacao',
            'Energia Elétrica'              => 'ocupacao',
            'Água/Esgoto'                   => 'ocupacao',
            'Gás'                           => 'ocupacao',
            'Internet'                      => 'ocupacao',
            'Telecomunicações'              => 'ocupacao',
            'Serviços de terceiros'         => 'administrativas',
            'Burocracia'                    => 'administrativas',
            'Tributos'                      => 'administrativas',
            'Outros'                        => 'administrativas',
            'Marketing'                     => 'marketing',
            'Tarifas bancárias'             => 'despesas_financeiras',
            'Juros e multas pagos'          => 'despesas_financeiras',
            'Reforma'                       => 'investimentos',
            'Equipamentos'                  => 'investimentos',
            'Retirada dos sócios'           => 'retiradas',
            'Empréstimo pago'               => 'retiradas',
        ],
    ];

    /** Nomes antigos (gravados por telas e integrações) que apontam para outra conta do plano. */
    private const SINONIMOS = [
        'saida' => ['servicos' => 'Serviços de terceiros', 'serviços' => 'Serviços de terceiros'],
    ];

    public function transacoes(): HasMany
    {
        return $this->hasMany(Transacao::class, 'categoria_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }

    /** Cria o plano padrão da clínica ativa se ela ainda não tiver nenhum. */
    public static function garantirPadroes(): void
    {
        if (static::query()->exists()) {
            return;
        }
        foreach (self::PADRAO as $tipo => $contas) {
            $ordem = 0;
            foreach ($contas as $nome => $grupo) {
                static::query()->create(['nome' => $nome, 'grupo' => $grupo, 'tipo' => $tipo, 'ativa' => true, 'ordem' => $ordem += 10]);
            }
        }
    }

    /** @return Collection<int, self> contas ativas do tipo, na ordem dos grupos */
    public static function doTipo(TipoTransacao|string $tipo): Collection
    {
        self::garantirPadroes();
        $tipo = $tipo instanceof TipoTransacao ? $tipo : TipoTransacao::from($tipo);
        $ordemGrupos = array_flip(array_map(fn (GrupoPlanoContas $g) => $g->value, GrupoPlanoContas::cases()));

        return static::query()->ativas()->where('tipo', $tipo->value)->select(['id', 'nome', 'grupo', 'tipo', 'ordem'])
            ->limit(300)->get()
            ->sortBy(fn (self $c) => sprintf('%03d-%05d-%s', $ordemGrupos[$c->grupo->value], $c->ordem, Str::lower($c->nome)))
            ->values();
    }

    /** @return array<int, string> nomes das contas ativas (para selects e validação) */
    public static function nomes(TipoTransacao|string $tipo): array
    {
        return self::doTipo($tipo)->pluck('nome')->all();
    }

    /**
     * Conta do plano para um nome de categoria (telas antigas, integrações, API).
     * Sem correspondência, cai em "Outros" do tipo.
     */
    public static function resolver(TipoTransacao|string $tipo, ?string $categoria, ?string $subcategoria = null): ?self
    {
        self::garantirPadroes();
        $tipo  = $tipo instanceof TipoTransacao ? $tipo->value : $tipo;
        $nome  = trim((string) $categoria);
        if ($tipo === 'saida' && mb_strtolower((string) $subcategoria) === 'comissões') {
            $nome = 'Comissões';
        }
        $nome = self::SINONIMOS[$tipo][mb_strtolower($nome)] ?? $nome;

        $contas = static::query()->where('tipo', $tipo)->select(['id', 'nome', 'grupo', 'tipo', 'ativa'])->limit(300)->get();

        return $contas->first(fn (self $c) => mb_strtolower($c->nome) === mb_strtolower($nome))
            ?? $contas->first(fn (self $c) => $c->nome === 'Outros')
            ?? $contas->first();
    }
}
