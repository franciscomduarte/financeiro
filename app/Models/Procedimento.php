<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedimento extends Model
{
    use BelongsToClinica;

    protected $table = 'procedimentos';

    protected $fillable = [
        'nome',
        'descricao',
        'duracao_minutos',
        'valor',
        'ativo',
    ];

    protected $casts = [
        'duracao_minutos' => 'integer',
        'valor'           => 'decimal:2',
        'ativo'           => 'boolean',
    ];

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'procedimento_id');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
