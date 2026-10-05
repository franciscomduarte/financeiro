<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AgendamentoController;
use App\Http\Controllers\Api\V1\AnexoController;
use App\Http\Controllers\Api\V1\BloqueioAgendaController;
use App\Http\Controllers\Api\V1\ContratoController;
use App\Http\Controllers\Api\V1\FornecedorController;
use App\Http\Controllers\Api\V1\ProcedimentoController;
use App\Http\Controllers\Api\V1\ProfissionalController;
use App\Http\Controllers\Api\V1\TaxaCartaoController;
use App\Http\Controllers\Api\V1\TransacaoController;
use App\Http\Controllers\CobrancaController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// ─── Webhooks públicos (sem autenticação) ────────────────────────────────────
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle'])
    ->name('whatsapp.webhook');

Route::post('/webhook/asaas', [CobrancaController::class, 'webhook'])
    ->name('webhook.asaas');

Route::middleware(['auth:sanctum', 'clinica'])->prefix('v1')->name('api.v1.')->group(function (): void {

    // Transações, anexos e download seguro de anexo
    Route::middleware('modulo:lancamentos')->group(function (): void {
        Route::apiResource('transacoes', TransacaoController::class)
            ->parameters(['transacoes' => 'transacao']);

        Route::prefix('transacoes/{transacao}/anexos')->group(function (): void {
            Route::get('/', [AnexoController::class, 'index']);
            Route::post('/', [AnexoController::class, 'store']);
            Route::delete('/{anexo}', [AnexoController::class, 'destroy']);
        });

        Route::get('anexos/{anexo}/download', [AnexoController::class, 'download'])
            ->name('anexos.download');
    });

    // Fornecedores
    Route::apiResource('fornecedores', FornecedorController::class)->middleware('modulo:administrativo')
        ->parameters(['fornecedores' => 'fornecedor']);

    // Contratos
    Route::middleware('modulo:administrativo')->group(function (): void {
        Route::apiResource('contratos', ContratoController::class)
            ->parameters(['contratos' => 'contrato']);
        Route::post('contratos/{contrato}/reajuste', [ContratoController::class, 'reajuste']);
        Route::post('contratos/{contrato}/arquivo', [ContratoController::class, 'uploadArquivo']);
        Route::get('contratos/{contrato}/arquivo/download', [ContratoController::class, 'downloadArquivo'])
            ->name('contratos.arquivo.download');
    });

    // Taxas de cartão
    Route::get('taxas-cartao', [TaxaCartaoController::class, 'index'])->middleware('modulo:taxas,lancamentos');
    Route::put('taxas-cartao/{taxaCartao}', [TaxaCartaoController::class, 'update'])->middleware('modulo:taxas');

    // Cobranças
    Route::prefix('cobrancas')->name('cobrancas.')->middleware('modulo:cobrancas')->group(function (): void {
        Route::get('/', [CobrancaController::class, 'index'])->name('index');
        Route::post('/disparar-todas', [CobrancaController::class, 'dispararTodas'])->name('disparar-todas');
        Route::post('/disparar/{pacienteId}', [CobrancaController::class, 'dispararManual'])->name('disparar');
        Route::post('/reenviar/{cobrancaId}', [CobrancaController::class, 'reenviar'])->name('reenviar');
        Route::post('/sincronizar-status', [CobrancaController::class, 'sincronizarStatus'])->name('sincronizar');
    });

    // ─── Agendamentos ─────────────────────────────────────────────────────────────
    Route::prefix('agendamentos')->name('agendamentos.')->middleware('modulo:agenda')->group(function (): void {
        Route::get('/', [AgendamentoController::class, 'index'])->name('index');
        Route::post('/', [AgendamentoController::class, 'store'])->name('store');
        Route::get('/slots', [AgendamentoController::class, 'slots'])->name('slots');
        Route::get('/{agendamento}', [AgendamentoController::class, 'show'])->name('show');
        Route::post('/{agendamento}/cancelar', [AgendamentoController::class, 'cancelar'])->name('cancelar');
        Route::post('/{agendamento}/reagendar', [AgendamentoController::class, 'reagendar'])->name('reagendar');
        Route::post('/{agendamento}/realizado', [AgendamentoController::class, 'realizado'])->name('realizado');
        Route::post('/{agendamento}/falta', [AgendamentoController::class, 'falta'])->name('falta');
    });

    // ─── Profissionais ────────────────────────────────────────────────────────────
    // Consulta: quem usa a agenda; alteração: só quem configura a agenda
    Route::prefix('profissionais')->name('profissionais.')->group(function (): void {
        Route::middleware('modulo:agenda,configuracao_agenda')->group(function (): void {
            Route::get('/', [ProfissionalController::class, 'index'])->name('index');
            Route::get('/{profissional}', [ProfissionalController::class, 'show'])->name('show');
            Route::get('/{profissional}/grade', [ProfissionalController::class, 'grade'])->name('grade');
            Route::get('/{profissional}/bloqueios', [BloqueioAgendaController::class, 'index'])->name('bloqueios.index');
        });
        Route::middleware('modulo:configuracao_agenda')->group(function (): void {
            Route::post('/', [ProfissionalController::class, 'store'])->name('store');
            Route::put('/{profissional}', [ProfissionalController::class, 'update'])->name('update');
            Route::delete('/{profissional}', [ProfissionalController::class, 'destroy'])->name('destroy');
            Route::put('/{profissional}/grade', [ProfissionalController::class, 'atualizarGrade'])->name('grade.update');
            Route::post('/{profissional}/bloqueios', [BloqueioAgendaController::class, 'store'])->name('bloqueios.store');
            Route::delete('/bloqueios/{bloqueio}', [BloqueioAgendaController::class, 'destroy'])->name('bloqueios.destroy');
        });
    });

    // ─── Procedimentos ────────────────────────────────────────────────────────────
    Route::apiResource('procedimentos', ProcedimentoController::class)->only(['index', 'show'])
        ->parameters(['procedimentos' => 'procedimento'])->middleware('modulo:agenda,configuracao_agenda');
    Route::apiResource('procedimentos', ProcedimentoController::class)->except(['index', 'show'])
        ->parameters(['procedimentos' => 'procedimento'])->middleware('modulo:configuracao_agenda');
});
