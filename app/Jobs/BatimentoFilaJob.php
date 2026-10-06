<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SaudeSistemaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/** Prova de vida da fila: se este job para de rodar, o monitor avisa que a fila parou. */
class BatimentoFilaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        Cache::put(SaudeSistemaService::CHAVE_BATIMENTO, now()->getTimestamp(), now()->addDay());
    }
}
