<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Consulta ou gravação em tabela da clínica sem clínica ativa definida (falha alto em vez de vazar dados). */
class ClinicaNaoDefinidaException extends RuntimeException
{
    public function __construct(string $model)
    {
        parent::__construct("Nenhuma clínica ativa definida ao acessar {$model}.");
    }
}
