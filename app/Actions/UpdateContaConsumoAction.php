<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ContaConsumo;
use Illuminate\Support\Facades\DB;

class UpdateContaConsumoAction
{
    public function execute(ContaConsumo $conta, array $data): ContaConsumo
    {
        return DB::transaction(function () use ($conta, $data): ContaConsumo {
            $conta->update($data);
            return $conta->fresh();
        });
    }
}
