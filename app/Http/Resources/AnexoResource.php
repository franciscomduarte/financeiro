<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnexoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'transacao_id'   => $this->transacao_id,
            'tipo'           => $this->tipo->value,
            'nome_arquivo'   => $this->nome_arquivo,
            'mime_type'      => $this->mime_type,
            'tamanho_bytes'  => $this->tamanho_bytes,
            'created_at'     => $this->created_at?->toIso8601String(),
            'download_url'   => route('api.v1.anexos.download', ['anexo' => $this->id]),
        ];
    }
}
