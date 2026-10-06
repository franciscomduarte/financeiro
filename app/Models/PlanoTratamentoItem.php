<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlanoTratamentoItem extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'plano_tratamento_itens';

    protected $fillable = ['plano_id', 'procedimento_id', 'descricao', 'sessoes', 'intervalo_dias', 'valor_unitario', 'ordem'];

    protected $casts = [
        'sessoes'        => 'integer',
        'intervalo_dias' => 'integer',
        'valor_unitario' => 'decimal:2',
        'ordem'          => 'integer',
    ];
}
