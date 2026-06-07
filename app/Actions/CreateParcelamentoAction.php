<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Parcelamento;

class CreateParcelamentoAction
{
    public function execute(array $data): Parcelamento
    {
        return Parcelamento::create($data);
    }
}
