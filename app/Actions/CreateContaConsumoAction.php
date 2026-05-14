<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ContaConsumo;
use Illuminate\Support\Facades\DB;

class CreateContaConsumoAction
{
    public function execute(array $data): ContaConsumo
    {
        return DB::transaction(function () use ($data): ContaConsumo {
            return ContaConsumo::create($data);
        });
    }
}
