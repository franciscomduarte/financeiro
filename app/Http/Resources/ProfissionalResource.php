<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfissionalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'nome'                => $this->nome,
            'email'               => $this->email,
            'telefone'            => $this->telefone,
            'google_calendar_id'  => $this->google_calendar_id,
            'google_configurado'  => ! empty($this->google_refresh_token) && ! empty($this->google_calendar_id),
            'cor_agenda'          => $this->cor_agenda,
            'ativo'               => $this->ativo,
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
