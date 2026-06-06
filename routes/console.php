<?php

use App\Http\Controllers\CobrancaController;
use App\Jobs\LembreteVencimentoJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Agendamentos de cobrança ────────────────────────────────────────────────

// Dispara cobranças para todos os pacientes ativos todo dia 1 do mês às 09:00
Schedule::call(fn () => app(CobrancaController::class)->dispararTodas())
    ->monthlyOn(1, '09:00')
    ->name('cobrancas-mensais')
    ->withoutOverlapping();

// Envia lembrete 1 dia antes do vencimento, diariamente às 10:00
Schedule::job(new LembreteVencimentoJob())->dailyAt('10:00');

// Sincroniza status de cobranças pendentes com o Asaas, diariamente às 08:00
Schedule::call(fn () => app(CobrancaController::class)->sincronizarStatus())
    ->dailyAt('08:00')
    ->name('sync-status-asaas')
    ->withoutOverlapping();
