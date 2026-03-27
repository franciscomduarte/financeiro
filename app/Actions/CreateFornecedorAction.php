<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Fornecedor;

class CreateFornecedorAction
{
    public function execute(array $data): Fornecedor
    {
        return Fornecedor::create($data);
    }
}
