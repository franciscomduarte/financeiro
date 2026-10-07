<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Parcela de uma venda no cartão que a maquininha ainda vai liberar (ou já liberou) para a conta. */
class RecebivelCartao extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'recebiveis_cartao';

    protected $fillable = [
        'transacao_baixa_id', 'transacao_id', 'conta_maquininha_id', 'parcela', 'total_parcelas', 'antecipado',
        'data_prevista', 'valor', 'taxa_antecipacao', 'valor_liquido', 'liquidado_em', 'transferencia_id', 'despesa_id',
    ];

    protected $casts = [
        'antecipado'       => 'boolean',
        'data_prevista'    => 'date',
        'liquidado_em'     => 'date',
        'valor'            => 'decimal:2',
        'taxa_antecipacao' => 'decimal:2',
        'valor_liquido'    => 'decimal:2',
        'parcela'          => 'integer',
        'total_parcelas'   => 'integer',
    ];

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }

    public function maquininha(): BelongsTo
    {
        return $this->belongsTo(ContaFinanceira::class, 'conta_maquininha_id');
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->whereNull('liquidado_em');
    }
}
