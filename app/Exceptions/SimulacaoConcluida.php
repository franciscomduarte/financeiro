<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Interrompe a transação no modo simulação: nada é gravado. */
final class SimulacaoConcluida extends RuntimeException {}
