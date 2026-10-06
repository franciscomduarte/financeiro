<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Produto aplicado no atendimento (toxina, preenchedor...). Baixa no estoque ao finalizar. */
class AtendimentoInjetavel extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'atendimento_injetaveis';

    protected $fillable = ['atendimento_id', 'product_id', 'batch_id', 'quantidade', 'regiao', 'observacao', 'lotes_baixados'];

    protected $casts = ['quantidade' => 'decimal:3'];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(StockProduct::class, 'product_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }
}
