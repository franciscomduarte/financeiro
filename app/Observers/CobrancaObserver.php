<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Financeiro\SincronizarCobrancaAction;
use App\Models\Cobranca;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Toda cobrança criada ou com situação alterada (job, webhook, sincronização) reflete no financeiro. */
class CobrancaObserver
{
    public function saved(Cobranca $cobranca): void
    {
        if (! $cobranca->wasRecentlyCreated && ! $cobranca->wasChanged('status')) {
            return;
        }
        try {
            app(SincronizarCobrancaAction::class)->execute($cobranca);
        } catch (Throwable $e) {
            // A cobrança já foi gravada no Asaas; o financeiro é acertado na próxima mudança
            Log::error('[Asaas] falha ao refletir a cobrança no financeiro', ['tenant_id' => $cobranca->tenant_id, 'cobranca' => $cobranca->id, 'erro' => $e->getMessage()]);
        }
    }
}
