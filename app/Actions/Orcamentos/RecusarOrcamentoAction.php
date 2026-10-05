<?php

declare(strict_types=1);

namespace App\Actions\Orcamentos;

use App\Enums\StatusOrcamento;
use App\Models\Orcamento;
use RuntimeException;

/** Marca o orçamento como recusado pelo paciente. */
class RecusarOrcamentoAction
{
    public function execute(string $orcamentoId): Orcamento
    {
        $orcamento = Orcamento::query()->findOrFail($orcamentoId);

        if ($orcamento->status !== StatusOrcamento::Aberto) {
            throw new RuntimeException('Só orçamentos em aberto podem ser recusados.');
        }

        $orcamento->update(['status' => StatusOrcamento::Recusado, 'decidido_em' => now()]);

        return $orcamento;
    }
}
