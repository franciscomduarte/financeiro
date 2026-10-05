<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Tentativa de gravar dados em clínica cujo teste grátis terminou (modo somente leitura). */
class ClinicaSomenteLeituraException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('O teste grátis terminou e o sistema está em modo somente leitura. Fale conosco para assinar.');
    }
}
