<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VozTransacaoController;
use App\Livewire\ContratoIndex;
use App\Livewire\FornecedorIndex;
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
    Route::redirect('/', '/transacoes');

    Route::get('/transacoes', TransacaoIndex::class)->name('transacoes.index');
    Route::get('/taxas-cartao', TaxasCartaoIndex::class)->name('taxas-cartao.index');
    Route::get('/fornecedores', FornecedorIndex::class)->name('web.fornecedores');
    Route::get('/contratos', ContratoIndex::class)->name('web.contratos');

    Route::post('/voz/transacao', [VozTransacaoController::class, 'processar'])->name('voz.transacao');
});
