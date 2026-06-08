<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgendamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'inicio_em'             => $this->inicio_em?->toIso8601String(),
            'fim_em'                => $this->fim_em?->toIso8601String(),
            'observacoes'           => $this->observacoes,
            'motivo_cancelamento'   => $this->motivo_cancelamento,
            'google_event_id'       => $this->google_event_id,
            'whatsapp_enviado_em'   => $this->whatsapp_enviado_em?->toIso8601String(),
            'email_enviado_em'      => $this->email_enviado_em?->toIso8601String(),
            'agendamento_origem_id' => $this->agendamento_origem_id,
            'paciente'              => $this->whenLoaded('paciente', fn () => [
                'id'       => $this->paciente->id,
                'nome'     => $this->paciente->nome,
                'telefone' => $this->paciente->telefone,
                'email'    => $this->paciente->email,
            ]),
            'profissional'          => $this->whenLoaded('profissional', fn () => [
                'id'         => $this->profissional->id,
                'nome'       => $this->profissional->nome,
                'cor_agenda' => $this->profissional->cor_agenda,
            ]),
            'procedimento'          => $this->whenLoaded('procedimento', fn () => [
                'id'               => $this->procedimento->id,
                'nome'             => $this->procedimento->nome,
                'duracao_minutos'  => $this->procedimento->duracao_minutos,
                'valor'            => $this->procedimento->valor,
            ]),
            'created_at'            => $this->created_at?->toIso8601String(),
        ];
    }
}
