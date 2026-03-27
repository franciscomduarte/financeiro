<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Fornecedor;

class UpdateFornecedorAction
{
    public function execute(Fornecedor $fornecedor, array $data): Fornecedor
    {
        $fornecedor->update($data);

        return $fornecedor->fresh();
    }
}
