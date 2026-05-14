<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ObrigacaoFiscal;
use Illuminate\Support\Facades\DB;

class CreateObrigacaoFiscalAction
{
    public function execute(array $data): ObrigacaoFiscal
    {
        return DB::transaction(fn () => ObrigacaoFiscal::create($data));
    }
}
