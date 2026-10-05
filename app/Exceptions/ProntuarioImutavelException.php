<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Registros do prontuário não podem ser alterados nem apagados depois de salvos. */
class ProntuarioImutavelException extends RuntimeException
{
    public function __construct(string $mensagem = 'Registros do prontuário não podem ser alterados. Para corrigir, registre uma nova evolução.')
    {
        parent::__construct($mensagem);
    }
}
