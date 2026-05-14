<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DocumentoCategoria;

class CreateDocumentoCategoriaAction
{
    public function execute(array $data): DocumentoCategoria
    {
        return DocumentoCategoria::create($data);
    }
}
