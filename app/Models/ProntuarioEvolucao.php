<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\ProntuarioImutavelException;
use App\Models\Concerns\RegistroDoProntuario;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Evolução do atendimento escrita pelo profissional. Não pode ser alterada nem apagada. */
class ProntuarioEvolucao extends Model
{
    use HasUuids, RegistroDoProntuario;

    protected $table = 'prontuario_evolucoes';

    protected $fillable = ['paciente_id', 'agendamento_id', 'profissional_id', 'user_id', 'texto'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new ProntuarioImutavelException());
        static::deleting(fn () => throw new ProntuarioImutavelException());
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }
}
