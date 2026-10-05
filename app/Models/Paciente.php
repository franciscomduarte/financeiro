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

    protected static function booted(): void
    {
        // Perfil Profissional: só os pacientes que atende
        static::addGlobalScope(new \App\Models\Scopes\ProfissionalScope());
    }

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
        'aceita_whatsapp_marketing',
        'aceita_email_marketing',
    ];

    protected $casts = [
        'status'             => StatusPaciente::class,
        'data_nascimento'    => 'date',
        'valor_mensalidade'  => 'decimal:2',
        'consentimento_em'   => 'datetime',
        'anonimizado_em'     => 'datetime',
        'aceita_whatsapp_marketing' => 'boolean',
        'aceita_email_marketing'    => 'boolean',
    ];

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path
            ? Storage::disk('public')->url($this->foto_path)
            : null;
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'paciente_id');
    }

    public function acessos(): HasMany
    {
        return $this->hasMany(PacienteAcesso::class, 'paciente_id');
    }

    public function anonimizado(): bool
    {
        return $this->anonimizado_em !== null;
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
