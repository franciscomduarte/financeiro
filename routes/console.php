<?php

use App\Console\Commands\CheckExpiredBatchesCommand;
use App\Http\Controllers\CobrancaController;
use App\Jobs\DisparadorParcelaJob;
use App\Jobs\LembreteVencimentoJob;
use App\Models\Parcelamento;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Support\ClinicaAtual;

// Multiclínica: tarefas que tocam dados da clínica rodam uma vez por clínica não bloqueada.
// Jobs despachados dentro do callback herdam a clínica (Context → fila).
$porClinica = fn (callable $tarefa) => fn () => app(ClinicaAtual::class)->paraCadaClinica($tarefa);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Agendamentos de cobrança ────────────────────────────────────────────────

// Dispara cobranças para todos os pacientes ativos todo dia 1 do mês às 09:00
Schedule::call($porClinica(fn () => app(CobrancaController::class)->dispararTodas()))
    ->monthlyOn(1, '09:00')
    ->name('cobrancas-mensais')
    ->withoutOverlapping();

// Envia lembrete 1 dia antes do vencimento, diariamente às 10:00
Schedule::call($porClinica(fn () => LembreteVencimentoJob::dispatch()))
    ->dailyAt('10:00')
    ->name('lembrete-vencimento-cobrancas');

// Sincroniza status de cobranças pendentes com o Asaas, diariamente às 08:00
Schedule::call($porClinica(fn () => app(CobrancaController::class)->sincronizarStatus()))
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
Schedule::call($porClinica(function () {
    $hoje = now()->day;
    Parcelamento::where('status', 'ativo')
        ->where('dia_cobranca', $hoje)
        ->get()
        ->each(fn (Parcelamento $p) => DisparadorParcelaJob::dispatch($p->id)->onQueue('cobrancas'));
}))
    ->dailyAt('09:05')
    ->name('parcelamentos-diario')
    ->withoutOverlapping();

// ─── Agendamentos — lembretes ─────────────────────────────────────────────────

// Verifica e envia lembretes de consulta a cada 15 minutos
Schedule::call($porClinica(fn () => \App\Jobs\EnviarLembretesAgendamentosJob::dispatch()))
    ->everyFifteenMinutes()
    ->name('lembretes-agendamentos')
    ->withoutOverlapping();

// ─── Alertas de vencimento ────────────────────────────────────────────────────

// Resumo diário (e-mail aos admins + WhatsApp da gestão) de contas, contratos e documentos
Schedule::call($porClinica(fn () => \App\Jobs\ResumoVencimentosJob::dispatch()))
    ->dailyAt('07:30')
    ->timezone('America/Sao_Paulo') // app roda em UTC; 07:30 no horário da clínica
    ->name('resumo-vencimentos')
    ->withoutOverlapping();

// ─── Plataforma: teste grátis ────────────────────────────────────────────────

// Avisa os admins das clínicas em teste 3 dias antes do fim e no último dia
Schedule::call(fn () => app(\App\Actions\AvisarFimDoTesteAction::class)->execute())
    ->dailyAt('08:00')
    ->timezone('America/Sao_Paulo')
    ->name('avisos-fim-do-teste')
    ->withoutOverlapping();

// ─── Lançamentos recorrentes ─────────────────────────────────────────────────

// Cria os lançamentos do mês das recorrências (aluguel, salários...). Roda todo dia, mas só gera
// o que ainda falta: na prática, no dia 1º (ou no 1º dia em que a rotina rodar no mês).
Schedule::call($porClinica(fn () => app(\App\Actions\GerarLancamentosRecorrentesAction::class)->execute()))
    ->dailyAt('06:00')
    ->timezone('America/Sao_Paulo')
    ->name('lancamentos-recorrentes')
    ->withoutOverlapping();
