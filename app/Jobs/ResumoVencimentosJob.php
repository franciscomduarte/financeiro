<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\EnviarResumoVencimentosAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Resumo diário de vencimentos (e-mail + WhatsApp). Canais já enviados no dia não se repetem nas novas tentativas. */
class ResumoVencimentosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [300, 900];

    public function handle(EnviarResumoVencimentosAction $action): void
    {
        $action->execute();
    }
}
