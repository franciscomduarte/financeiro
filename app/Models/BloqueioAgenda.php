<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloqueioAgenda extends Model
{
    protected $table = 'bloqueios_agenda';

    protected $fillable = [
        'profissional_id',
        'inicio_em',
        'fim_em',
        'motivo',
    ];

    protected $casts = [
        'inicio_em' => 'datetime',
        'fim_em'    => 'datetime',
    ];

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }
}
