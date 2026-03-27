<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'tipo'                    => $this->tipo->value,
            'fase'                    => $this->fase->value,
            'categoria'               => $this->categoria,
            'subcategoria'            => $this->subcategoria,
            'centro_custo'            => $this->centro_custo,
            'descricao'               => $this->descricao,
            'cliente'                 => $this->cliente,
            'fornecedor_id'           => $this->fornecedor_id,
            'valor_bruto'             => $this->valor_bruto,
            'taxa_operacional'        => $this->taxa_operacional,
            'imposto_estimado'        => $this->imposto_estimado,
            'valor_liquido'           => $this->valor_liquido,
            'data_competencia'        => $this->data_competencia?->toDateString(),
            'data_pagamento'          => $this->data_pagamento?->toDateString(),
            'forma_pagamento'         => $this->forma_pagamento->value,
            'num_parcelas'            => $this->num_parcelas,
            'parcela_atual'           => $this->parcela_atual,
            'status'                  => $this->status->value,
            'recorrencia'             => $this->recorrencia->value,
            'data_inicio_recorrencia' => $this->data_inicio_recorrencia?->toDateString(),
            'transacao_pai_id'        => $this->transacao_pai_id,
            'observacoes'             => $this->observacoes,
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
            'anexos'                  => AnexoResource::collection($this->whenLoaded('anexos')),
        ];
    }
}
