<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusAgendamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agendamento extends Model
{
    use HasUuids;

    protected $table = 'agendamentos';

    protected $fillable = [
        'paciente_id',
        'profissional_id',
        'procedimento_id',
        'procedimentos_ids',
        'inicio_em',
        'fim_em',
        'status',
        'observacoes',
        'motivo_cancelamento',
        'agendamento_origem_id',
        'google_event_id',
        'whatsapp_enviado_em',
        'email_enviado_em',
        'lembrete_1dia_em',
        'lembrete_2horas_em',
    ];

    protected $casts = [
        'procedimentos_ids'   => 'array',
        'inicio_em'           => 'datetime',
        'fim_em'              => 'datetime',
        'whatsapp_enviado_em' => 'datetime',
        'email_enviado_em'    => 'datetime',
        'lembrete_1dia_em'    => 'datetime',
        'lembrete_2horas_em'  => 'datetime',
        'status'              => StatusAgendamento::class,
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'procedimento_id');
    }

    public function agendamentoOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'agendamento_origem_id');
    }

    public function reagendamentos(): HasMany
    {
        return $this->hasMany(self::class, 'agendamento_origem_id');
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value]);
    }

    public function scopeHoje(Builder $query): Builder
    {
        return $query->whereDate('inicio_em', today());
    }

    public function scopePorProfissional(Builder $query, string $profissionalId): Builder
    {
        return $query->where('profissional_id', $profissionalId);
    }

    public function scopePorStatus(Builder $query, StatusAgendamento $status): Builder
    {
        return $query->where('status', $status->value);
    }
}
