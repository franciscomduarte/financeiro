<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusAtendimento;
use App\Enums\VisibilidadeAtendimento;
use App\Models\Concerns\RegistroDoProntuario;
use App\Models\Scopes\VisibilidadeAtendimentoScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Atendimento do paciente: fichas preenchidas, injetáveis e plano. Finalizado não muda mais. */
#[ScopedBy(VisibilidadeAtendimentoScope::class)]
class Atendimento extends Model
{
    use HasUuids, RegistroDoProntuario;

    protected $fillable = [
        'paciente_id', 'agendamento_id', 'profissional_id', 'user_id', 'status', 'visibilidade',
        'iniciado_em', 'finalizado_em', 'duracao_segundos',
    ];

    protected $casts = [
        'status'           => StatusAtendimento::class,
        'visibilidade'     => VisibilidadeAtendimento::class,
        'iniciado_em'      => 'datetime',
        'finalizado_em'    => 'datetime',
        'duracao_segundos' => 'integer',
    ];

    public function emAndamento(): bool
    {
        return $this->status === StatusAtendimento::EmAndamento;
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function fichas(): HasMany
    {
        return $this->hasMany(AtendimentoFicha::class, 'atendimento_id');
    }

    public function injetaveis(): HasMany
    {
        return $this->hasMany(AtendimentoInjetavel::class, 'atendimento_id');
    }

    public function plano(): HasOne
    {
        return $this->hasOne(PlanoTratamento::class, 'atendimento_id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(ProntuarioAnexo::class, 'atendimento_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(ProntuarioFoto::class, 'atendimento_id');
    }

    /** "1h 05min", "12min" */
    public function duracaoTexto(): ?string
    {
        if ($this->duracao_segundos === null) {
            return null;
        }
        $min = intdiv($this->duracao_segundos, 60);

        return $min >= 60 ? intdiv($min, 60) . 'h ' . str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT) . 'min' : max(1, $min) . 'min';
    }
}
