<?php

declare(strict_types=1);

namespace App\Actions\Pacotes;

use App\Enums\StatusPacote;
use App\Models\Pacote;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Cancela o saldo restante do pacote. A receita da venda não muda: estorno é feito em Lançamentos. */
class CancelarPacoteAction
{
    public function execute(string $pacoteId): Pacote
    {
        $pacote = Pacote::query()->findOrFail($pacoteId);

        if ($pacote->status !== StatusPacote::Ativo) {
            throw new RuntimeException('Só pacotes ativos podem ser cancelados.');
        }

        $pacote->update(['status' => StatusPacote::Cancelado]);

        Log::warning('[Pacotes] pacote cancelado', [
            'pacote_id' => $pacote->id, 'saldo_cancelado' => $pacote->saldo(), 'user_id' => auth()->id(),
        ]);

        return $pacote;
    }
}
