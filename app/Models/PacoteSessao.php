<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sessão de um pacote usada num atendimento. */
class PacoteSessao extends Model
{
    use BelongsToClinica;

    protected $table = 'pacote_sessoes';

    protected $fillable = ['pacote_id', 'agendamento_id', 'profissional_id', 'usada_em'];

    protected $casts = ['usada_em' => 'date'];

    public function pacote(): BelongsTo
    {
        return $this->belongsTo(Pacote::class, 'pacote_id');
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }
}
