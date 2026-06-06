<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AnexoController;
use App\Http\Controllers\Api\V1\ContratoController;
use App\Http\Controllers\Api\V1\FornecedorController;
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

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function (): void {

    // Transações
    Route::apiResource('transacoes', TransacaoController::class)
        ->parameters(['transacoes' => 'transacao']);

    // Anexos de transações
    Route::prefix('transacoes/{transacao}/anexos')->group(function (): void {
        Route::get('/', [AnexoController::class, 'index']);
        Route::post('/', [AnexoController::class, 'store']);
        Route::delete('/{anexo}', [AnexoController::class, 'destroy']);
    });

    // Download seguro de anexo
    Route::get('anexos/{anexo}/download', [AnexoController::class, 'download'])
        ->name('anexos.download');

    // Fornecedores
    Route::apiResource('fornecedores', FornecedorController::class)
        ->parameters(['fornecedores' => 'fornecedor']);

    // Contratos
    Route::apiResource('contratos', ContratoController::class)
        ->parameters(['contratos' => 'contrato']);
    Route::post('contratos/{contrato}/reajuste', [ContratoController::class, 'reajuste']);
    Route::post('contratos/{contrato}/arquivo', [ContratoController::class, 'uploadArquivo']);
    Route::get('contratos/{contrato}/arquivo/download', [ContratoController::class, 'downloadArquivo'])
        ->name('contratos.arquivo.download');

    // Taxas de cartão
    Route::get('taxas-cartao', [TaxaCartaoController::class, 'index']);
    Route::put('taxas-cartao/{taxaCartao}', [TaxaCartaoController::class, 'update']);

    // Cobranças
    Route::prefix('cobrancas')->name('cobrancas.')->group(function (): void {
        Route::get('/', [CobrancaController::class, 'index'])->name('index');
        Route::post('/disparar-todas', [CobrancaController::class, 'dispararTodas'])->name('disparar-todas');
        Route::post('/disparar/{pacienteId}', [CobrancaController::class, 'dispararManual'])->name('disparar');
        Route::post('/reenviar/{cobrancaId}', [CobrancaController::class, 'reenviar'])->name('reenviar');
        Route::post('/sincronizar-status', [CobrancaController::class, 'sincronizarStatus'])->name('sincronizar');
    });
});
