<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcedimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'nome'            => $this->nome,
            'descricao'       => $this->descricao,
            'duracao_minutos' => $this->duracao_minutos,
            'valor'           => $this->valor,
            'ativo'           => $this->ativo,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
