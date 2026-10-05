<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoModeloProntuario;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Modelo de termo de consentimento ou de orientações, com campos como {paciente} e {data}. */
class ProntuarioModelo extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'prontuario_modelos';

    protected $fillable = ['tipo', 'titulo', 'conteudo', 'ativo'];

    protected $casts = [
        'tipo'  => TipoModeloProntuario::class,
        'ativo' => 'boolean',
    ];
}
