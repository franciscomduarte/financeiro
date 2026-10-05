<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AcaoAcessoPaciente;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Quem acessou ou alterou os dados de um paciente (LGPD). Só leitura pelo Model; gravação em RegistroAcessoPaciente. */
class PacienteAcesso extends Model
{
    use BelongsToClinica;

    public const UPDATED_AT = null;

    protected $table = 'paciente_acessos';

    protected $casts = ['acao' => AcaoAcessoPaciente::class];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
