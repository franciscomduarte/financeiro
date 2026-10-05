<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Clínica (tenant). Tabelas da clínica referenciam esta pelo campo tenant_id. */
class Clinica extends Model
{
    use HasUuids;

    protected $table = 'clinicas';

    protected $fillable = [
        'nome',
        'slug',
        'status',
        'teste_ate',
        'whatsapp_numero',
    ];

    protected $casts = [
        'status'    => StatusClinica::class,
        'teste_ate' => 'date',
    ];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'clinica_user')
            ->withPivot('papel')
            ->withTimestamps();
    }

    public function estaBloqueada(): bool
    {
        return $this->status === StatusClinica::Bloqueada;
    }
}
