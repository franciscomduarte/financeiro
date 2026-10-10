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
        'sexo',
        'estado_civil',
        'profissao',
        'endereco',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'codigo_municipio',
        'origem',
    ];

    /** Opções sugeridas na ficha (o campo aceita outros valores vindos de importação). */
    public const SEXOS = ['Feminino', 'Masculino', 'Outro'];
    public const ESTADOS_CIVIS = ['Solteiro(a)', 'Casado(a)', 'União estável', 'Divorciado(a)', 'Viúvo(a)'];
    public const ORIGENS = ['Instagram', 'Indicação', 'Facebook', 'Google', 'WhatsApp', 'Passou na frente', 'Outro'];

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

    public function pacotes(): HasMany
    {
        return $this->hasMany(Pacote::class, 'paciente_id');
    }

    public function evolucoes(): HasMany
    {
        return $this->hasMany(ProntuarioEvolucao::class, 'paciente_id');
    }

    public function anexosProntuario(): HasMany
    {
        return $this->hasMany(ProntuarioAnexo::class, 'paciente_id');
    }

    public function fotosProntuario(): HasMany
    {
        return $this->hasMany(ProntuarioFoto::class, 'paciente_id');
    }

    public function termos(): HasMany
    {
        return $this->hasMany(ProntuarioTermo::class, 'paciente_id');
    }

    public function orientacoes(): HasMany
    {
        return $this->hasMany(ProntuarioOrientacao::class, 'paciente_id');
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
