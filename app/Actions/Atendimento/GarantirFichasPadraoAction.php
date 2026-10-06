<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Models\FichaModelo;
use App\Support\FichasPadrao;
use Illuminate\Support\Facades\DB;

/** Na primeira vez que a clínica usa o atendimento, cria as fichas padrão (a clínica edita depois). */
class GarantirFichasPadraoAction
{
    public function execute(): void
    {
        if (FichaModelo::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            foreach (FichasPadrao::todas() as $ordem => $ficha) {
                FichaModelo::create([...$ficha, 'ordem' => $ordem, 'ativo' => true]);
            }
        });
    }
}
