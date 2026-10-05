<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Item do orçamento: procedimento (ou serviço avulso), quantidade de sessões e valor. */
class OrcamentoItem extends Model
{
    use BelongsToClinica;

    public $timestamps = false;

    protected $table = 'orcamento_itens';

    protected $fillable = ['orcamento_id', 'procedimento_id', 'descricao', 'quantidade', 'valor_unitario', 'subtotal'];

    protected $casts = [
        'quantidade'     => 'integer',
        'valor_unitario' => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'procedimento_id');
    }
}
