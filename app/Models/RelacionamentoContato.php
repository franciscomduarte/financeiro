<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoContatoRelacionamento;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Contato de relacionamento já feito (evita mandar a mesma mensagem duas vezes). */
class RelacionamentoContato extends Model
{
    use BelongsToClinica;

    public const UPDATED_AT = null;

    protected $table = 'relacionamento_contatos';

    protected $fillable = ['paciente_id', 'tipo', 'referencia', 'canal', 'user_id'];

    protected $casts = ['tipo' => TipoContatoRelacionamento::class];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
