<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contrato;
use Illuminate\Support\Facades\DB;

class UpdateContratoAction
{
    public function execute(Contrato $contrato, array $data): Contrato
    {
        return DB::transaction(function () use ($contrato, $data): Contrato {
            $contrato->update($data);

            return $contrato->fresh();
        });
    }
}
