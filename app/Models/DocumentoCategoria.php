<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoCategoria extends Model
{
    use BelongsToClinica, HasUuids;

    protected $fillable = [
        'nome',
        'descricao',
        'cor',
        'requer_validade',
        'alerta_dias_antes',
    ];

    protected $casts = [
        'requer_validade'   => 'boolean',
        'alerta_dias_antes' => 'integer',
    ];

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'categoria_id');
    }

    public function corClasses(): string
    {
        return match ($this->cor) {
            'red'    => 'bg-red-100 text-red-700',
            'orange' => 'bg-orange-100 text-orange-700',
            'amber'  => 'bg-amber-100 text-amber-700',
            'green'  => 'bg-green-100 text-green-700',
            'blue'   => 'bg-blue-100 text-blue-700',
            'indigo' => 'bg-indigo-100 text-indigo-700',
            'purple' => 'bg-purple-100 text-purple-700',
            default  => 'bg-slate-100 text-slate-700',
        };
    }
}
