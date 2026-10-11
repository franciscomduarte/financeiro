<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Agenda\EnviarAgendaProfissionaisAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Véspera às 19h: agenda do dia seguinte por e-mail para cada profissional. Quem já recebeu não recebe de novo nas novas tentativas. */
class AgendaProfissionaisJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [300, 900];

    public function handle(EnviarAgendaProfissionaisAction $action): void
    {
        $action->execute();
    }
}
