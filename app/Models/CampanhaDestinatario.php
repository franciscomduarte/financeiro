<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusDestinatarioCampanha;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampanhaDestinatario extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'campanha_destinatarios';

    protected $fillable = ['campanha_id', 'paciente_id', 'status', 'enviado_em', 'mensagem_id', 'erro'];

    protected $casts = [
        'status'     => StatusDestinatarioCampanha::class,
        'enviado_em' => 'datetime',
    ];

    public function campanha(): BelongsTo
    {
        return $this->belongsTo(Campanha::class, 'campanha_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
