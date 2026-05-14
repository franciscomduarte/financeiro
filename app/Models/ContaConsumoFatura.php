<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusFatura;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaConsumoFatura extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'conta_consumo_faturas';

    protected $fillable = [
        'conta_consumo_id',
        'competencia',
        'data_vencimento',
        'data_pagamento',
        'valor',
        'consumo',
        'status',
        'transacao_id',
        'arquivo_path',
        'observacoes',
    ];

    protected $casts = [
        'status'          => StatusFatura::class,
        'valor'           => 'decimal:2',
        'consumo'         => 'array',
        'data_vencimento' => 'date',
        'data_pagamento'  => 'date',
    ];

    public function contaConsumo(): BelongsTo
    {
        return $this->belongsTo(ContaConsumo::class, 'conta_consumo_id');
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }

    public function competenciaFormatada(): string
    {
        [$ano, $mes] = explode('-', $this->competencia);
        $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        return ($meses[(int)$mes - 1]) . '/' . $ano;
    }

    public function consumoFormatado(): ?string
    {
        if (empty($this->consumo)) {
            return null;
        }
        $parts = [];
        foreach ($this->consumo as $chave => $valor) {
            $parts[] = $valor . ' ' . strtoupper($chave);
        }
        return implode(' | ', $parts);
    }
}
