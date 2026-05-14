<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoContaConsumo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaConsumo extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'contas_consumo';

    protected $fillable = [
        'fornecedor_id',
        'tipo',
        'descricao',
        'dia_vencimento',
        'valor_estimado',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'tipo'            => TipoContaConsumo::class,
        'valor_estimado'  => 'decimal:2',
        'dia_vencimento'  => 'integer',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function faturas(): HasMany
    {
        return $this->hasMany(ContaConsumoFatura::class, 'conta_consumo_id')->orderByDesc('competencia');
    }

    public function ultimaFatura(): HasMany
    {
        return $this->hasMany(ContaConsumoFatura::class, 'conta_consumo_id')->orderByDesc('competencia')->limit(1);
    }

    public function isAtiva(): bool
    {
        return $this->status === 'ativo';
    }
}
