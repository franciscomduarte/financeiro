<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MomentoFoto;
use App\Models\Concerns\RegistroDoProntuario;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Foto do prontuário (antes, depois, acompanhamento). Arquivo privado, servido só com permissão. */
class ProntuarioFoto extends Model
{
    use HasUuids, RegistroDoProntuario;

    protected $table = 'prontuario_fotos';

    protected $fillable = [
        'paciente_id', 'agendamento_id', 'atendimento_id', 'user_id', 'momento', 'regiao', 'descricao',
        'tirada_em', 'arquivo_path', 'miniatura_path', 'mime', 'tamanho',
    ];

    protected $casts = [
        'momento'   => MomentoFoto::class,
        'tirada_em' => 'date',
    ];
}
