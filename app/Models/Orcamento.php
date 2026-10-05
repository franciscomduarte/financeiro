<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\StatusOrcamento;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Orçamento de procedimentos para o paciente. Aprovado, vira receita e pacotes de sessões. */
class Orcamento extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'orcamentos';

    protected $fillable = [
        'numero', 'paciente_id', 'user_id', 'status', 'validade', 'subtotal', 'desconto', 'total',
        'observacoes', 'forma_pagamento', 'enviado_em', 'decidido_em', 'transacao_id',
    ];

    protected $casts = [
        'status'          => StatusOrcamento::class,
        'forma_pagamento' => FormaPagamento::class,
        'validade'        => 'date',
        'subtotal'        => 'decimal:2',
        'desconto'        => 'decimal:2',
        'total'           => 'decimal:2',
        'numero'          => 'integer',
        'enviado_em'      => 'datetime',
        'decidido_em'     => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class, 'orcamento_id');
    }

    public function pacotes(): HasMany
    {
        return $this->hasMany(Pacote::class, 'orcamento_id');
    }

    public function vencido(): bool
    {
        return $this->status === StatusOrcamento::Aberto && $this->validade->isBefore(today());
    }

    /** Rótulo e cor da situação, contando a validade. */
    public function situacao(): array
    {
        return $this->vencido()
            ? ['Vencido', 'bg-red-50 text-red-700']
            : [$this->status->label(), $this->status->badge()];
    }

    public function codigo(): string
    {
        return '#' . str_pad((string) $this->numero, 4, '0', STR_PAD_LEFT);
    }
}
