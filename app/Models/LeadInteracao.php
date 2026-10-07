<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoInteracaoLead;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Linha do tempo do lead: contatos, anotações, mudanças de etapa. Não é editada. */
class LeadInteracao extends Model
{
    use BelongsToClinica, HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'lead_interacoes';

    protected $fillable = ['lead_id', 'user_id', 'tipo', 'texto', 'mensagem_id'];

    protected $casts = ['tipo' => TipoInteracaoLead::class];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Mensagem da conversa no WhatsApp (recebida, ou enviada com id da Evolution). */
    public function ehMensagemWhatsApp(): bool
    {
        return $this->tipo === TipoInteracaoLead::WhatsAppRecebido
            || ($this->tipo === TipoInteracaoLead::WhatsAppEnviado && $this->mensagem_id !== null);
    }
}
