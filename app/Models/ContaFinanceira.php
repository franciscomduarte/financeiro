<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\TipoContaFinanceira;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Onde o dinheiro da clínica está: caixa, conta bancária, maquininha. */
class ContaFinanceira extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'contas_financeiras';

    protected $fillable = [
        'nome', 'tipo', 'banco', 'agencia', 'numero', 'saldo_inicial', 'saldo_inicial_em', 'padrao', 'ativa',
    ];

    protected $casts = [
        'tipo'             => TipoContaFinanceira::class,
        'saldo_inicial'    => 'decimal:2',
        'saldo_inicial_em' => 'date',
        'padrao'           => 'boolean',
        'ativa'            => 'boolean',
    ];

    /** Contas criadas para toda clínica (a migration cria as mesmas para as clínicas existentes). */
    public const PADRAO = [
        ['nome' => 'Caixa', 'tipo' => 'caixa', 'padrao' => false],
        ['nome' => 'Conta bancária', 'tipo' => 'banco', 'padrao' => true],
        ['nome' => 'Maquininha', 'tipo' => 'maquininha', 'padrao' => false],
    ];

    public function baixas(): HasMany
    {
        return $this->hasMany(TransacaoBaixa::class, 'conta_financeira_id');
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativa', true);
    }

    /** Cria as contas padrão da clínica ativa se ela ainda não tiver nenhuma. */
    public static function garantirPadroes(): void
    {
        if (static::query()->exists()) {
            return;
        }
        foreach (self::PADRAO as $conta) {
            static::query()->create($conta + ['saldo_inicial' => 0, 'ativa' => true]);
        }
    }

    /** Conta sugerida para a forma de pagamento: dinheiro no caixa, cartão na maquininha, o resto na padrão. */
    public static function sugeridaPara(?FormaPagamento $forma): ?self
    {
        self::garantirPadroes();

        $tipo = match (true) {
            $forma === FormaPagamento::Dinheiro                              => TipoContaFinanceira::Caixa,
            $forma !== null && ($forma === FormaPagamento::Debito || str_starts_with($forma->value, 'credito')) => TipoContaFinanceira::Maquininha,
            default                                                          => null,
        };

        $contas = static::query()->ativas()->select(['id', 'nome', 'tipo', 'padrao'])->orderByDesc('padrao')->orderBy('nome')->limit(50)->get();

        return ($tipo ? $contas->firstWhere('tipo', $tipo) : null)
            ?? $contas->firstWhere('padrao', true)
            ?? $contas->first();
    }
}
