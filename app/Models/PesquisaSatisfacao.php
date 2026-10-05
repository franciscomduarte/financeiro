<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pesquisa de satisfação (nota de 0 a 10) enviada ao paciente após o atendimento. */
class PesquisaSatisfacao extends Model
{
    use BelongsToClinica, HasUuids;

    protected static function booted(): void
    {
        // Perfil Profissional: só as pesquisas dos próprios pacientes
        static::addGlobalScope(new \App\Models\Scopes\ProfissionalScope());
    }

    protected $table = 'pesquisas_satisfacao';

    protected $fillable = [
        'paciente_id', 'agendamento_id', 'profissional_id', 'token', 'user_id',
        'enviada_em', 'respondida_em', 'nota', 'comentario',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'enviada_em'    => 'datetime',
        'respondida_em' => 'datetime',
        'nota'          => 'integer',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function link(): string
    {
        return route('avaliacao.mostrar', $this->token);
    }
}
