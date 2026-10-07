<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EtapaLead;
use App\Enums\OrigemLead;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Interessado que ainda não é paciente, acompanhado no funil até fechar ou ser perdido. */
class Lead extends Model
{
    use BelongsToClinica, HasUuids;

    protected $fillable = [
        'nome', 'telefone', 'telefone_chave', 'whatsapp_lid', 'email', 'origem', 'procedimento_id', 'interesse', 'etapa', 'motivo_perda',
        'responsavel_id', 'proximo_contato_em', 'primeiro_contato_em', 'ultima_interacao_em', 'paciente_id', 'convertido_em',
        'observacoes', 'consentimento_em',
    ];

    protected $casts = [
        'origem'              => OrigemLead::class,
        'etapa'               => EtapaLead::class,
        'proximo_contato_em'  => 'datetime',
        'primeiro_contato_em' => 'datetime',
        'ultima_interacao_em' => 'datetime',
        'convertido_em'       => 'datetime',
        'consentimento_em'    => 'datetime',
    ];

    public function interacoes(): HasMany
    {
        return $this->hasMany(LeadInteracao::class, 'lead_id')->latest('created_at')->orderByDesc('id'); // id (UUID v7) desempata no mesmo segundo
    }

    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'procedimento_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function contatoAtrasado(): bool
    {
        return $this->etapa->aberta() && $this->proximo_contato_em !== null && $this->proximo_contato_em->isPast();
    }

    /** Lead novo sem nenhum contato da clínica há mais de 1 hora. */
    public function semResposta(): bool
    {
        return $this->etapa === EtapaLead::Novo && $this->primeiro_contato_em === null && $this->created_at?->lt(now()->subHour());
    }

    /** Número para wa.me (55 + DDD + número). */
    public function whatsappLink(?string $texto = null): ?string
    {
        $d = \App\Support\Telefone::nacional($this->telefone);

        return $d === null ? null : 'https://wa.me/55' . $d . ($texto ? '?text=' . rawurlencode($texto) : '');
    }

    /** Conversa direto no WhatsApp Web (computador), sem a página intermediária do wa.me. */
    public function whatsappWebLink(?string $texto = null): ?string
    {
        $d = \App\Support\Telefone::nacional($this->telefone);

        return $d === null ? null : 'https://web.whatsapp.com/send?phone=55' . $d . ($texto ? '&text=' . rawurlencode($texto) : '');
    }
}
