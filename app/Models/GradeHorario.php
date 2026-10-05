<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeHorario extends Model
{
    use BelongsToClinica;

    protected $table = 'grade_horarios';

    protected $fillable = [
        'profissional_id',
        'dia_semana',
        'hora_inicio',
        'hora_fim',
        'intervalo_inicio',
        'intervalo_fim',
        'ativo',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'ativo'      => 'boolean',
    ];

    /** Intervalo (pausa) do dia em "H:i", ou null se não houver. */
    public function intervalo(): ?array
    {
        if (! $this->intervalo_inicio || ! $this->intervalo_fim) {
            return null;
        }

        return [substr((string) $this->intervalo_inicio, 0, 5), substr((string) $this->intervalo_fim, 0, 5)];
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }
}
