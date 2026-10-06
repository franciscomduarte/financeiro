<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\StatusAgendamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[\Illuminate\Database\Eloquent\Attributes\ObservedBy(\App\Observers\AgendamentoObserver::class)]
class Agendamento extends Model
{
    use BelongsToClinica, HasUuids;

    protected static function booted(): void
    {
        // Perfil Profissional: só a própria agenda e os próprios pacientes
        static::addGlobalScope(new \App\Models\Scopes\ProfissionalScope());
    }

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

    public function pesquisa(): HasOne
    {
        return $this->hasOne(PesquisaSatisfacao::class, 'agendamento_id');
    }

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

    public function receita(): HasOne
    {
        return $this->hasOne(Transacao::class, 'agendamento_id');
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

    /**
     * Instante real do horário. A agenda grava a hora de parede da clínica sem fuso
     * (14:00 fica 14:00 no banco); para comparar com now() é preciso ler nesse fuso.
     */
    public function inicioReal(): \Carbon\CarbonImmutable
    {
        return \Carbon\CarbonImmutable::parse($this->inicio_em->format('Y-m-d H:i:s'), (string) config('clinica.fuso_horario'))->utc();
    }
}
