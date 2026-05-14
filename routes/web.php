<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentoDownloadController;
use App\Http\Controllers\VozTransacaoController;
use App\Livewire\ContaConsumoIndex;
use App\Livewire\DashboardIndex;
use App\Livewire\DocumentoIndex;
use App\Livewire\RelatorioIndex;
use App\Livewire\ContratoIndex;
use App\Livewire\FornecedorIndex;
use App\Livewire\ObrigacaoFiscalIndex;
use App\Livewire\TaxasCartaoIndex;
use App\Livewire\TransacaoIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─── Auth ───────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Rotas protegidas ───────────────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::redirect('/', '/dashboard');

    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');
    Route::get('/relatorio', RelatorioIndex::class)->name('web.relatorio');
    Route::get('/transacoes', TransacaoIndex::class)->name('transacoes.index');
    Route::get('/taxas-cartao', TaxasCartaoIndex::class)->name('taxas-cartao.index');
    Route::get('/fornecedores', FornecedorIndex::class)->name('web.fornecedores');
    Route::get('/contratos', ContratoIndex::class)->name('web.contratos');
    Route::get('/contas-consumo', ContaConsumoIndex::class)->name('web.contas-consumo');
    Route::get('/obrigacoes-fiscais', ObrigacaoFiscalIndex::class)->name('web.obrigacoes-fiscais');

    Route::post('/voz/transacao', [VozTransacaoController::class, 'processar'])->name('voz.transacao');

    Route::get('/documentos', DocumentoIndex::class)->name('web.documentos');
    Route::get('/documentos/{id}/download', [DocumentoDownloadController::class, 'download'])->name('documentos.download');
    Route::get('/documentos/versao/{id}/download', [DocumentoDownloadController::class, 'downloadVersao'])->name('documentos.versao.download');
});
