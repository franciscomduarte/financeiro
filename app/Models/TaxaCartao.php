<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TaxaCartao extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'taxas_cartao';

    protected $fillable = [
        'modalidade',
        'percentual',
        'ativo',
    ];

    protected $casts = [
        'percentual' => 'decimal:2',
        'ativo'      => 'boolean',
    ];
}
