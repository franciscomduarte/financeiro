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

        \Illuminate\Support\Facades\DB::transaction(function () use ($pacote): void {
            $pacote->update(['status' => StatusPacote::Cancelado]);

            // Venda ainda não paga: a conta a receber deixa de existir. Já paga: a devolução é combinada com o paciente.
            $venda = $pacote->transacao_id ? \App\Models\Transacao::query()->find($pacote->transacao_id) : null;
            if ($venda !== null && $venda->status === \App\Enums\StatusTransacao::Pendente && (float) $venda->valor_pago <= 0
                && ! \App\Models\Pacote::query()->where('transacao_id', $venda->id)->whereKeyNot($pacote->id)->where('status', StatusPacote::Ativo->value)->exists()) {
                app(\App\Actions\UpdateTransacaoAction::class)->execute($venda, ['status' => \App\Enums\StatusTransacao::Cancelado->value]);
            }
        });

        Log::warning('[Pacotes] pacote cancelado', [
            'pacote_id' => $pacote->id, 'saldo_cancelado' => $pacote->saldo(), 'user_id' => auth()->id(),
        ]);

        return $pacote;
    }
}
