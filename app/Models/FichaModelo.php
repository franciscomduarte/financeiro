<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Modelo de ficha do atendimento (Anamnese, Capilar, Facial...), editável pela clínica. */
class FichaModelo extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'fichas_modelos';

    protected $fillable = ['nome', 'descricao', 'campos', 'ordem', 'ativo'];

    protected $casts = [
        'campos' => 'array',
        'ordem'  => 'integer',
        'ativo'  => 'boolean',
    ];
}
