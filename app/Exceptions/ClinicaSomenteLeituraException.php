<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Tentativa de gravar dados com a clínica em modo somente leitura (teste encerrado, acesso de suporte ou perfil somente consulta). */
class ClinicaSomenteLeituraException extends RuntimeException
{
    public const TESTE_ENCERRADO = 'O teste grátis terminou e o sistema está em modo somente leitura. Fale conosco para assinar.';
    public const SUPORTE         = 'Acesso de suporte: somente leitura. Nada pode ser criado ou alterado.';
    public const PERFIL          = 'Seu perfil é somente consulta: dá para ver, mas não criar, alterar ou excluir. Fale com o administrador da clínica.';

    public function __construct(string $mensagem = self::TESTE_ENCERRADO)
    {
        parent::__construct($mensagem);
    }
}
