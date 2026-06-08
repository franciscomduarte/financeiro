<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profissional extends Model
{
    use HasUuids;

    protected $table = 'profissionais';

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'google_calendar_id',
        'google_refresh_token',
        'cor_agenda',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    protected $hidden = [
        'google_refresh_token',
    ];

    public function gradeHorarios(): HasMany
    {
        return $this->hasMany(GradeHorario::class, 'profissional_id');
    }

    public function bloqueiosAgenda(): HasMany
    {
        return $this->hasMany(BloqueioAgenda::class, 'profissional_id');
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'profissional_id');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
