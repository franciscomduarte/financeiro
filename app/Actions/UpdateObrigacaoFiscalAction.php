<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ObrigacaoFiscal;
use Illuminate\Support\Facades\DB;

class UpdateObrigacaoFiscalAction
{
    public function execute(ObrigacaoFiscal $obrigacao, array $data): ObrigacaoFiscal
    {
        return DB::transaction(function () use ($obrigacao, $data): ObrigacaoFiscal {
            $obrigacao->update($data);
            return $obrigacao->fresh();
        });
    }
}
