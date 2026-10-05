<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Support\ClinicaAtual;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

/** Job de teste: registra qual clínica estava ativa quando ele rodou. */
class RegistraClinicaDoJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(ClinicaAtual $clinicaAtual): void
    {
        Cache::put('clinica-do-job', $clinicaAtual->id(), 60);
    }
}
