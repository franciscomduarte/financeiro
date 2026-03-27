<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratoReajusteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'contrato_id'        => $this->contrato_id,
            'data_reajuste'      => $this->data_reajuste?->toDateString(),
            'valor_anterior'     => $this->valor_anterior,
            'valor_novo'         => $this->valor_novo,
            'indice'             => $this->indice,
            'percentual_efetivo' => $this->percentual_efetivo,
            'observacoes'        => $this->observacoes,
            'created_at'         => $this->created_at?->toDateTimeString(),
        ];
    }
}
