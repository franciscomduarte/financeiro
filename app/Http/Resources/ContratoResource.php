<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'fornecedor_id'              => $this->fornecedor_id,
            'fornecedor'                 => new FornecedorResource($this->whenLoaded('fornecedor')),
            'valor_mensal'               => $this->valor_mensal,
            'data_inicio'                => $this->data_inicio?->toDateString(),
            'data_fim'                   => $this->data_fim?->toDateString(),
            'periodicidade_reajuste'     => $this->periodicidade_reajuste?->value,
            'data_proximo_reajuste'      => $this->data_proximo_reajuste?->toDateString(),
            'indice_reajuste'            => $this->indice_reajuste?->value,
            'multa_rescisao_valor'       => $this->multa_rescisao_valor,
            'multa_rescisao_percentual'  => $this->multa_rescisao_percentual,
            'aviso_previo_dias'          => $this->aviso_previo_dias,
            'tem_arquivo'                => $this->temArquivo(),
            'arquivo_contrato_nome'      => $this->arquivo_contrato_nome,
            'link_contrato'              => $this->link_contrato,
            'risco'                      => $this->risco->value,
            'status'                     => $this->status->value,
            'observacoes'                => $this->observacoes,
            'reajustes'                  => ContratoReajusteResource::collection($this->whenLoaded('reajustes')),
            'created_at'                 => $this->created_at?->toDateString(),
            'updated_at'                 => $this->updated_at?->toDateString(),
        ];
    }
}
