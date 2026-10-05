<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\ProntuarioImutavelException;
use App\Models\Concerns\RegistroDoProntuario;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Orientações entregues ao paciente (pós-procedimento, prescrição). Só a data de envio muda depois. */
class ProntuarioOrientacao extends Model
{
    use HasUuids, RegistroDoProntuario;

    protected $table = 'prontuario_orientacoes';

    protected $fillable = ['paciente_id', 'agendamento_id', 'user_id', 'titulo', 'texto', 'enviada_whatsapp_em'];

    protected $casts = ['enviada_whatsapp_em' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (self $orientacao): void {
            if (array_diff(array_keys($orientacao->getDirty()), ['enviada_whatsapp_em', 'updated_at']) !== []) {
                throw new ProntuarioImutavelException('Orientações entregues não podem ser alteradas. Registre uma nova.');
            }
        });
        static::deleting(fn () => throw new ProntuarioImutavelException('Orientações entregues não podem ser apagadas.'));
    }
}
