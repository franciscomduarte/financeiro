<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Dinheiro passando de uma conta da clínica para outra (ex.: maquininha → banco, depósito do caixa). */
class Transferencia extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'transferencias';

    protected $fillable = ['conta_origem_id', 'conta_destino_id', 'data', 'valor', 'descricao', 'user_id'];

    protected $casts = [
        'data'  => 'date',
        'valor' => 'decimal:2',
    ];

    public function origem(): BelongsTo
    {
        return $this->belongsTo(ContaFinanceira::class, 'conta_origem_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(ContaFinanceira::class, 'conta_destino_id');
    }
}
