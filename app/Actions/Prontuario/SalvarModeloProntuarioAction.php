<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Enums\TipoModeloProntuario;
use App\Models\ProntuarioModelo;

/** Cria ou altera um modelo de termo ou de orientações. Termos já assinados guardam o próprio texto. */
class SalvarModeloProntuarioAction
{
    public function execute(?string $id, TipoModeloProntuario $tipo, string $titulo, string $conteudo, bool $ativo = true): ProntuarioModelo
    {
        $modelo = $id ? ProntuarioModelo::findOrFail($id) : new ProntuarioModelo();

        $modelo->fill([
            'tipo'     => $tipo,
            'titulo'   => trim($titulo),
            'conteudo' => trim($conteudo),
            'ativo'    => $ativo,
        ])->save();

        return $modelo;
    }
}
