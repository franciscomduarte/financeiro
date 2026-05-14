<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DocumentoCategoria;

class UpdateDocumentoCategoriaAction
{
    public function execute(DocumentoCategoria $categoria, array $data): DocumentoCategoria
    {
        $categoria->update($data);

        return $categoria->fresh();
    }
}
