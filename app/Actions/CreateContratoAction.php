<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contrato;
use Illuminate\Support\Facades\DB;

class CreateContratoAction
{
    public function execute(array $data): Contrato
    {
        return DB::transaction(function () use ($data): Contrato {
            return Contrato::create($data);
        });
    }
}
