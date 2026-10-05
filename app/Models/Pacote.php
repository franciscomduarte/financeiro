<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusPacote;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pacote de sessões vendido ao paciente; cada atendimento realizado pode usar uma sessão. */
class Pacote extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'pacotes';

    protected $fillable = [
        'paciente_id', 'procedimento_id', 'orcamento_id', 'transacao_id', 'user_id',
        'nome', 'sessoes_total', 'sessoes_usadas', 'valor_total', 'validade', 'status',
    ];

    protected $casts = [
        'status'         => StatusPacote::class,
        'sessoes_total'  => 'integer',
        'sessoes_usadas' => 'integer',
        'valor_total'    => 'decimal:2',
        'validade'       => 'date',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'procedimento_id');
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }

    public function sessoes(): HasMany
    {
        return $this->hasMany(PacoteSessao::class, 'pacote_id');
    }

    public function saldo(): int
    {
        return max(0, $this->sessoes_total - $this->sessoes_usadas);
    }

    public function vencido(): bool
    {
        return $this->validade !== null && $this->validade->isBefore(today());
    }

    /** Valor de cada sessão (para comissão): total dividido pelas sessões. */
    public function valorPorSessao(): float
    {
        return $this->sessoes_total > 0 ? (float) $this->valor_total / $this->sessoes_total : 0.0;
    }

    /** Ativos, com saldo e dentro da validade: os que podem ser usados num atendimento. */
    public function scopeUtilizaveis(Builder $query): Builder
    {
        return $query->where('status', StatusPacote::Ativo)
            ->whereColumn('sessoes_usadas', '<', 'sessoes_total')
            ->where(fn (Builder $q) => $q->whereNull('validade')->orWhereDate('validade', '>=', today()));
    }
}
