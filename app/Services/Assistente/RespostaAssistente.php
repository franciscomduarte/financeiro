<?php

declare(strict_types=1);

namespace App\Services\Assistente;

/** Resultado de uma rodada do assistente. */
final readonly class RespostaAssistente
{
    public function __construct(
        public string $texto,
        public int $tokensEntrada = 0,
        public int $tokensSaida = 0,
        public bool $recusou = false,
    ) {}
}
