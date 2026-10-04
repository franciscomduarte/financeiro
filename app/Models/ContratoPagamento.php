<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoPagamento extends Model
{
    use BelongsToClinica, HasFactory, HasUuids;

    protected $table = 'contrato_pagamentos';

    protected $fillable = [
        'contrato_id',
        'competencia',
        'valor',
        'data_pagamento',
        'forma_pagamento',
        'transacao_id',
        'observacoes',
    ];

    protected $casts = [
        'valor'          => 'decimal:2',
        'data_pagamento' => 'date',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }

    public function competenciaFormatada(): string
    {
        [$ano, $mes] = explode('-', $this->competencia);
        $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return ($meses[(int) $mes - 1]) . '/' . $ano;
    }
}
