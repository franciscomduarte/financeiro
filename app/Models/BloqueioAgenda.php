<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloqueioAgenda extends Model
{
    use BelongsToClinica;

    protected $table = 'bloqueios_agenda';

    protected $fillable = [
        'profissional_id',
        'grupo_id',
        'inicio_em',
        'fim_em',
        'dia_inteiro',
        'motivo',
    ];

    protected $casts = [
        'inicio_em'   => 'datetime',
        'fim_em'      => 'datetime',
        'dia_inteiro' => 'boolean',
    ];

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }
}
