<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Parcelamento;
use RuntimeException;

class CancelarParcelamentoAction
{
    public function execute(Parcelamento $parcelamento): void
    {
        if ($parcelamento->status !== 'ativo') {
            throw new RuntimeException('Apenas parcelamentos ativos podem ser cancelados.');
        }

        $parcelamento->update(['status' => 'cancelado']);
    }
}
