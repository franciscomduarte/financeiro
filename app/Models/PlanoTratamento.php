<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RegistroDoProntuario;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Plano de tratamento proposto no atendimento: procedimentos, sessões e intervalo. */
class PlanoTratamento extends Model
{
    use HasUuids, RegistroDoProntuario;

    protected $table = 'planos_tratamento';

    protected $fillable = ['paciente_id', 'atendimento_id', 'orcamento_id', 'user_id', 'observacoes'];

    public function itens(): HasMany
    {
        return $this->hasMany(PlanoTratamentoItem::class, 'plano_id')->orderBy('ordem');
    }

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    public function total(): float
    {
        return (float) $this->itens->sum(fn (PlanoTratamentoItem $i) => $i->sessoes * (float) $i->valor_unitario);
    }
}
