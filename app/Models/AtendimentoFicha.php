<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ficha preenchida no atendimento: cópia dos campos do modelo + respostas. */
class AtendimentoFicha extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'atendimento_fichas';

    protected $fillable = ['atendimento_id', 'modelo_id', 'titulo', 'campos', 'respostas'];

    protected $casts = [
        'campos'    => 'array',
        'respostas' => 'array',
    ];

    public function atendimento(): BelongsTo
    {
        return $this->belongsTo(Atendimento::class, 'atendimento_id');
    }

    /** Alguma resposta preenchida (para esconder fichas vazias no histórico). */
    public function preenchida(): bool
    {
        return collect($this->respostas ?? [])->contains(fn ($v) => is_array($v) ? $v !== [] : trim(strip_tags((string) $v)) !== '');
    }
}
