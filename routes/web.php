<?php

use App\Http\Controllers\ArquivoDownloadController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CadastroController;
use App\Http\Controllers\ClinicaController;
use App\Http\Controllers\DocumentoDownloadController;
use App\Http\Controllers\EsqueciSenhaController;
use App\Http\Controllers\VerificacaoEmailController;
use App\Http\Controllers\VozTransacaoController;
use App\Livewire\AdminUsuarioIndex;
use App\Livewire\ConfiguracaoClinica;
use App\Livewire\CobrancaIndex;
use App\Livewire\ContaConsumoIndex;
use App\Livewire\DashboardIndex;
use App\Livewire\DocumentoIndex;
use App\Livewire\MinhaConta;
use App\Livewire\RelatorioIndex;
use App\Livewire\ContratoIndex;
use App\Livewire\FornecedorIndex;
use App\Livewire\ObrigacaoFiscalIndex;
use App\Livewire\TaxasCartaoIndex;
use App\Livewire\AgendamentoConfiguracaoIndex;
use App\Livewire\AgendamentoIndex;
use App\Livewire\EstoqueIndex;
use App\Livewire\EstoqueMovimentacaoIndex;
use App\Livewire\EstoqueProdutoIndex;
use App\Livewire\PacienteIndex;
use App\Livewire\TransacaoIndex;
use Illuminate\Support\Facades\Route;

// Página do produto (visitante) ou sistema (logado)
Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))->name('home');

// ─── "Assine já": autocadastro com teste grátis ─────────────────
Route::get('/assine', [CadastroController::class, 'create'])->name('cadastro');
Route::post('/assine', [CadastroController::class, 'store'])->name('cadastro.store')->middleware(['guest', 'throttle:cadastro']);

// Confirmação de e-mail: o link do e-mail funciona mesmo sem login
Route::get('/email/confirmar/{id}/{hash}', [VerificacaoEmailController::class, 'confirmar'])
    ->name('verificacao.confirmar')->middleware(['signed', 'throttle:6,1']);

// ─── Auth ───────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Recuperação de senha ────────────────────────────────────────
Route::get('/esqueci-senha', [EsqueciSenhaController::class, 'showForm'])->name('password.request');
Route::post('/esqueci-senha', [EsqueciSenhaController::class, 'sendLink'])->name('password.email');
Route::get('/redefinir-senha/{token}', [EsqueciSenhaController::class, 'showReset'])->name('password.reset');
Route::post('/redefinir-senha', [EsqueciSenhaController::class, 'reset'])->name('password.update');

// ─── Rotas protegidas ───────────────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::get('/email/confirmar', [VerificacaoEmailController::class, 'aviso'])->name('verificacao.aviso');
    Route::post('/email/reenviar', [VerificacaoEmailController::class, 'reenviar'])
        ->name('verificacao.reenviar')->middleware('throttle:3,1');

    // Multiclínica: escolha/troca da clínica ativa
    Route::get('/clinicas/escolher', [ClinicaController::class, 'escolher'])->name('clinicas.escolher');
    Route::post('/clinicas/{clinica}/ativar', [ClinicaController::class, 'ativar'])->name('clinicas.ativar');

    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');
    Route::get('/relatorio', RelatorioIndex::class)->name('web.relatorio');
    Route::get('/pacientes', PacienteIndex::class)->name('pacientes.index');
    Route::get('/cobrancas', CobrancaIndex::class)->name('cobrancas.index');
    Route::get('/transacoes', TransacaoIndex::class)->name('transacoes.index');
    Route::get('/taxas-cartao', TaxasCartaoIndex::class)->name('taxas-cartao.index');
    Route::get('/fornecedores', FornecedorIndex::class)->name('web.fornecedores');
    Route::get('/contratos', ContratoIndex::class)->name('web.contratos');
    Route::get('/contas-consumo', ContaConsumoIndex::class)->name('web.contas-consumo');
    Route::get('/obrigacoes-fiscais', ObrigacaoFiscalIndex::class)->name('web.obrigacoes-fiscais');

    Route::post('/voz/transacao', [VozTransacaoController::class, 'processar'])->name('voz.transacao');

    Route::get('/minha-conta', MinhaConta::class)->name('minha-conta');

    Route::middleware('admin')->prefix('admin')->group(function (): void {
        Route::get('/usuarios', AdminUsuarioIndex::class)->name('admin.usuarios');
        Route::get('/clinica', ConfiguracaoClinica::class)->name('admin.clinica');
    });

    Route::get('/documentos', DocumentoIndex::class)->name('web.documentos');
    Route::get('/documentos/{id}/download', [DocumentoDownloadController::class, 'download'])->name('documentos.download');
    Route::get('/documentos/versao/{id}/download', [DocumentoDownloadController::class, 'downloadVersao'])->name('documentos.versao.download');

    Route::get('/agenda', AgendamentoIndex::class)->name('agenda.index');
    Route::get('/agenda/configuracao', AgendamentoConfiguracaoIndex::class)->name('agenda.configuracao');
    Route::get('/agenda/google/auth/{profissional}', [GoogleCalendarController::class, 'authorize'])->name('agenda.google.auth');
    Route::get('/auth/google/callback', [GoogleCalendarController::class, 'callback'])->name('agenda.google.callback');
    Route::post('/agenda/google/desconectar/{profissional}', [GoogleCalendarController::class, 'desconectar'])->name('agenda.google.desconectar');

    Route::get('/estoque', EstoqueIndex::class)->name('estoque.index');
    Route::get('/estoque/produtos', EstoqueProdutoIndex::class)->name('estoque.produtos');
    Route::get('/estoque/movimentacoes', EstoqueMovimentacaoIndex::class)->name('estoque.movimentacoes');

    Route::get('/faturas/{id}/arquivo', [ArquivoDownloadController::class, 'downloadFatura'])->name('faturas.arquivo.download');
    Route::get('/lancamentos-fiscais/{id}/arquivo', [ArquivoDownloadController::class, 'downloadGuiaFiscal'])->name('lancamentos-fiscais.arquivo.download');
    Route::get('/contratos/{id}/arquivo', [ArquivoDownloadController::class, 'downloadContrato'])->name('contratos.arquivo.download');
});
