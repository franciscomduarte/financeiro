<?php

use App\Console\Commands\CheckExpiredBatchesCommand;
use App\Http\Controllers\CobrancaController;
use App\Jobs\DisparadorParcelaJob;
use App\Jobs\LembreteVencimentoJob;
use App\Models\Parcelamento;
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

// ─── Agendamentos de estoque ─────────────────────────────────────────────────

// Expira frascos abertos cujo beyond-use date passou, a cada hora
Schedule::command('stock:check-expired-batches')
    ->hourly()
    ->name('stock-expire-batches')
    ->withoutOverlapping();

// Relatório diário de estoque mínimo às 07:00
Schedule::command('stock:daily-report')
    ->dailyAt('07:00')
    ->name('stock-daily-report');

// ─── Parcelamentos ────────────────────────────────────────────────────────────

// Dispara parcelas cujo dia_cobranca == hoje, diariamente às 09:05
Schedule::call(function () {
    $hoje = now()->day;
    Parcelamento::where('status', 'ativo')
        ->where('dia_cobranca', $hoje)
        ->get()
        ->each(fn (Parcelamento $p) => DisparadorParcelaJob::dispatch($p->id)->onQueue('cobrancas'));
})
    ->dailyAt('09:05')
    ->name('parcelamentos-diario')
    ->withoutOverlapping();

// ─── Agendamentos — lembretes ─────────────────────────────────────────────────

// Verifica e envia lembretes de consulta a cada 15 minutos
Schedule::job(new \App\Jobs\EnviarLembretesAgendamentosJob())
    ->everyFifteenMinutes()
    ->name('lembretes-agendamentos')
    ->withoutOverlapping();

// ─── Alertas de vencimento ────────────────────────────────────────────────────

// Resumo diário (e-mail aos admins + WhatsApp da gestão) de contas, contratos e documentos
Schedule::job(new \App\Jobs\ResumoVencimentosJob())
    ->dailyAt('07:30')
    ->timezone('America/Sao_Paulo') // app roda em UTC; 07:30 no horário da clínica
    ->name('resumo-vencimentos')
    ->withoutOverlapping();
