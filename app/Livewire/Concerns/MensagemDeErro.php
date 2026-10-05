<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use DomainException;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use LogicException;
use PDOException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

/**
 * Mensagem de erro para a tela: regras de negócio (exceções lançadas de propósito pelo sistema)
 * aparecem como estão; falhas técnicas (banco, rede, bugs) são registradas no log e a pessoa
 * vê uma mensagem amigável, sem detalhes internos.
 */
trait MensagemDeErro
{
    protected function mensagemDeErro(Throwable $e, string $padrao = 'Não foi possível concluir a ação'): string
    {
        if (self::erroDeNegocio($e)) {
            return $e->getMessage();
        }

        report($e);

        return rtrim($padrao, " .:") . '. Tente de novo em instantes.';
    }

    private static function erroDeNegocio(Throwable $e): bool
    {
        if ($e instanceof QueryException || $e instanceof PDOException || $e instanceof \Error) {
            return false;
        }

        if (str_starts_with($e::class, 'App\\Exceptions\\')) {
            return true;
        }

        return in_array($e::class, [
            RuntimeException::class, DomainException::class, InvalidArgumentException::class,
            LogicException::class, UnexpectedValueException::class,
        ], true);
    }
}
