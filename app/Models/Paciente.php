<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\StatusPaciente;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Paciente extends Model
{
    use BelongsToClinica, HasFactory, HasUuids;

    protected $table = 'pacientes';

    protected $fillable = [
        'nome',
        'cpf',
        'data_nascimento',
        'telefone',
        'email',
        'valor_mensalidade',
        'forma_pagamento',
        'anamnese',
        'observacoes',
        'status',
    ];

    protected $casts = [
        'status'             => StatusPaciente::class,
        'data_nascimento'    => 'date',
        'valor_mensalidade'  => 'decimal:2',
    ];

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path
            ? Storage::disk('public')->url($this->foto_path)
            : null;
    }

    public function transacoes(): HasMany
    {
        return $this->hasMany(Transacao::class, 'paciente_id');
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class, 'paciente_id');
    }
}
