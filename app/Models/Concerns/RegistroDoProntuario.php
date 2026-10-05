<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Scopes\ProfissionalScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro do prontuário de um paciente: pertence à clínica, segue o escopo do profissional
 * e guarda quem registrou.
 */
trait RegistroDoProntuario
{
    use BelongsToClinica;

    public static function bootRegistroDoProntuario(): void
    {
        static::addGlobalScope(new ProfissionalScope());
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
