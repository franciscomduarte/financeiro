<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Item do treinamento do assistente: o que ele pode dizer aos leads (procedimentos, dúvidas, regras da clínica). */
class AssistenteConhecimento extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'assistente_conhecimentos';

    protected $fillable = ['titulo', 'conteudo', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
