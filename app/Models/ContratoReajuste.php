<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoReajuste extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'contratos_reajustes';

    public $timestamps = false;

    protected $fillable = [
        'contrato_id',
        'data_reajuste',
        'valor_anterior',
        'valor_novo',
        'indice',
        'percentual_efetivo',
        'observacoes',
        'created_at',
    ];

    protected $casts = [
        'data_reajuste'      => 'date',
        'valor_anterior'     => 'decimal:2',
        'valor_novo'         => 'decimal:2',
        'percentual_efetivo' => 'decimal:2',
        'created_at'         => 'datetime',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
