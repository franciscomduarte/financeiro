<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FornecedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'nome_fantasia'               => $this->nome_fantasia,
            'razao_social'                => $this->razao_social,
            'cnpj'                        => $this->cnpj,
            'servico_prestado'            => $this->servico_prestado,
            'categoria'                   => $this->categoria,
            'contato_nome'                => $this->contato_nome,
            'contato_telefone'            => $this->contato_telefone,
            'contato_email'               => $this->contato_email,
            'contato_emergencia_nome'     => $this->contato_emergencia_nome,
            'contato_emergencia_telefone' => $this->contato_emergencia_telefone,
            'status'                      => $this->status->value,
            'observacoes'                 => $this->observacoes,
            'contratos_count'             => $this->whenCounted('contratos'),
            'created_at'                  => $this->created_at?->toDateString(),
        ];
    }
}
