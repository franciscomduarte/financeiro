<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxaCartaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'modalidade' => $this->modalidade,
            'percentual' => (float) $this->percentual,
            'ativo'      => $this->ativo,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
