<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\TipoTransacao;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pagamento ou recebimento (total ou parcial) de um lançamento: é o que movimenta o saldo da conta.
 * valor_movimentado = valor + juros + multa − desconto − taxa da maquininha.
 */
class TransacaoBaixa extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'transacao_baixas';

    protected $fillable = [
        'transacao_id', 'conta_financeira_id', 'tipo', 'data', 'valor', 'juros', 'multa', 'desconto', 'taxa',
        'valor_movimentado', 'forma_pagamento', 'antecipado', 'observacoes', 'user_id',
    ];

    protected $casts = [
        'tipo'              => TipoTransacao::class,
        'forma_pagamento'   => FormaPagamento::class,
        'data'              => 'date',
        'valor'             => 'decimal:2',
        'juros'             => 'decimal:2',
        'multa'             => 'decimal:2',
        'desconto'          => 'decimal:2',
        'taxa'              => 'decimal:2',
        'valor_movimentado' => 'decimal:2',
        'antecipado'        => 'boolean',
    ];

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(ContaFinanceira::class, 'conta_financeira_id');
    }

    public function recebiveis(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecebivelCartao::class, 'transacao_baixa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
