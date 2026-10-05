<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\ProntuarioImutavelException;
use App\Models\Concerns\RegistroDoProntuario;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Termo de consentimento assinado pelo paciente. Depois de assinado, não muda. */
class ProntuarioTermo extends Model
{
    use HasUuids, RegistroDoProntuario;

    protected $table = 'prontuario_termos';

    protected $fillable = [
        'paciente_id', 'modelo_id', 'agendamento_id', 'user_id', 'titulo', 'conteudo',
        'assinante_nome', 'assinatura_path', 'hash', 'assinado_em', 'ip',
    ];

    protected $casts = ['assinado_em' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new ProntuarioImutavelException('Termos assinados não podem ser alterados.'));
        static::deleting(fn () => throw new ProntuarioImutavelException('Termos assinados não podem ser apagados.'));
    }
}
