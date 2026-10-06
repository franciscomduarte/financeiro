<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CanalNotificacao;
use App\Enums\StatusNotificacao;
use App\Enums\TipoNotificacao;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mensagem ao paciente (WhatsApp ou e-mail): enviada, agendada ou com falha. */
class Notificacao extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'notificacoes';

    protected $fillable = [
        'paciente_id', 'tipo', 'canal', 'status', 'destinatario_nome', 'destino', 'assunto', 'conteudo',
        'origem_type', 'origem_id', 'agendada_para', 'enviada_em', 'entregue_em', 'lida_em', 'id_externo', 'erro', 'tentativas',
    ];

    protected $casts = [
        'tipo'          => TipoNotificacao::class,
        'canal'         => CanalNotificacao::class,
        'status'        => StatusNotificacao::class,
        'agendada_para' => 'datetime',
        'enviada_em'    => 'datetime',
        'entregue_em'   => 'datetime',
        'lida_em'       => 'datetime',
        'tentativas'    => 'integer',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /** Registro que originou a notificação (agendamento, cobrança...), sem lazy loading. */
    public function origem(): ?Model
    {
        if ($this->origem_type === null || $this->origem_id === null || ! class_exists($this->origem_type)) {
            return null;
        }

        return $this->origem_type::query()->find($this->origem_id);
    }
}
