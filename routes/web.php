<?php

use App\Http\Controllers\ArquivoDownloadController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CadastroController;
use App\Http\Controllers\ClinicaController;
use App\Http\Controllers\DocumentoDownloadController;
use App\Http\Controllers\EsqueciSenhaController;
use App\Http\Controllers\OrcamentoPdfController;
use App\Http\Controllers\PacienteExportacaoController;
use App\Livewire\ComissaoIndex;
use App\Livewire\OrcamentoIndex;
use App\Livewire\PacoteIndex;
use App\Http\Controllers\ProntuarioArquivoController;
use App\Livewire\Prontuario;
use App\Livewire\ProntuarioModelos;
use App\Http\Controllers\SuporteClinicaController;
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
use App\Livewire\PlataformaIndex;
use App\Livewire\RecorrenciaIndex;
use App\Livewire\TransacaoIndex;
use Illuminate\Support\Facades\Route;

// Página do produto (visitante) ou sistema (logado)
Route::get('/', fn () => auth()->check() ? redirect()->route('inicio') : view('welcome'))->name('home');

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

    // Tela inicial conforme o perfil (admin/financeiro: Início; recepção/profissional: Agenda)
    Route::get('/inicio', fn () => redirect()->route(auth()->user()->paginaInicial()))->name('inicio');

    // Cada área só abre para os perfis com acesso (RoleUsuario::modulos())
    Route::get('/dashboard', DashboardIndex::class)->name('dashboard')->middleware('modulo:inicio');
    Route::get('/relatorio', RelatorioIndex::class)->name('web.relatorio')->middleware('modulo:relatorios');
    Route::middleware('modulo:pacientes')->group(function (): void {
        Route::get('/pacientes', PacienteIndex::class)->name('pacientes.index');
        Route::get('/pacientes/{id}/exportar', PacienteExportacaoController::class)->name('pacientes.exportar');
    });
    // Prontuário: só quem vê dados clínicos (admin, profissional)
    Route::middleware('modulo:dados_clinicos')->group(function (): void {
        Route::get('/pacientes/{id}/prontuario', Prontuario::class)->name('pacientes.prontuario');
        Route::get('/prontuario/modelos', ProntuarioModelos::class)->name('prontuario.modelos');
        Route::get('/prontuario/fotos/{id}/{tamanho?}', [ProntuarioArquivoController::class, 'foto'])
            ->whereIn('tamanho', ['mini'])->name('prontuario.foto');
        Route::get('/prontuario/termos/{id}/pdf', [ProntuarioArquivoController::class, 'termo'])->name('prontuario.termo.pdf');
        Route::get('/prontuario/orientacoes/{id}/pdf', [ProntuarioArquivoController::class, 'orientacao'])->name('prontuario.orientacao.pdf');
    });
    Route::middleware('modulo:cobrancas')->group(function (): void {
        Route::get('/cobrancas', CobrancaIndex::class)->name('cobrancas.index');
        Route::get('/orcamentos', OrcamentoIndex::class)->name('orcamentos.index');
        Route::get('/orcamentos/{id}/pdf', OrcamentoPdfController::class)->name('orcamentos.pdf');
        Route::get('/pacotes', PacoteIndex::class)->name('pacotes.index');
    });
    Route::get('/comissoes', ComissaoIndex::class)->name('comissoes.index')->middleware('modulo:relatorios');
    Route::middleware('modulo:lancamentos')->group(function (): void {
        Route::get('/transacoes', TransacaoIndex::class)->name('transacoes.index');
        Route::get('/transacoes/recorrencias', RecorrenciaIndex::class)->name('transacoes.recorrencias');
        Route::post('/voz/transacao', [VozTransacaoController::class, 'processar'])->name('voz.transacao');
    });
    Route::get('/taxas-cartao', TaxasCartaoIndex::class)->name('taxas-cartao.index')->middleware('modulo:taxas');
    Route::middleware('modulo:administrativo')->group(function (): void {
        Route::get('/fornecedores', FornecedorIndex::class)->name('web.fornecedores');
        Route::get('/contratos', ContratoIndex::class)->name('web.contratos');
        Route::get('/contas-consumo', ContaConsumoIndex::class)->name('web.contas-consumo');
        Route::get('/obrigacoes-fiscais', ObrigacaoFiscalIndex::class)->name('web.obrigacoes-fiscais');
        Route::get('/documentos', DocumentoIndex::class)->name('web.documentos');
        Route::get('/documentos/{id}/download', [DocumentoDownloadController::class, 'download'])->name('documentos.download');
        Route::get('/documentos/versao/{id}/download', [DocumentoDownloadController::class, 'downloadVersao'])->name('documentos.versao.download');
        Route::get('/faturas/{id}/arquivo', [ArquivoDownloadController::class, 'downloadFatura'])->name('faturas.arquivo.download');
        Route::get('/lancamentos-fiscais/{id}/arquivo', [ArquivoDownloadController::class, 'downloadGuiaFiscal'])->name('lancamentos-fiscais.arquivo.download');
        Route::get('/contratos/{id}/arquivo', [ArquivoDownloadController::class, 'downloadContrato'])->name('contratos.arquivo.download');
    });

    Route::get('/minha-conta', MinhaConta::class)->name('minha-conta');

    // Painel do dono da plataforma (super admin): funciona sem clínica ativa
    Route::middleware('super-admin')->prefix('plataforma')->name('plataforma.')->group(function (): void {
        Route::get('/', PlataformaIndex::class)->name('index');
        Route::post('/suporte/sair', [SuporteClinicaController::class, 'sair'])->name('suporte.sair');
        Route::post('/suporte/{clinica}', [SuporteClinicaController::class, 'entrar'])->name('suporte.entrar');
    });

    Route::prefix('admin')->group(function (): void {
        Route::get('/usuarios', AdminUsuarioIndex::class)->name('admin.usuarios')->middleware('modulo:usuarios');
        Route::get('/clinica', ConfiguracaoClinica::class)->name('admin.clinica')->middleware('modulo:dados_clinica');
    });


    Route::get('/agenda', AgendamentoIndex::class)->name('agenda.index')->middleware('modulo:agenda');
    Route::middleware('modulo:configuracao_agenda')->group(function (): void {
        Route::get('/agenda/configuracao', AgendamentoConfiguracaoIndex::class)->name('agenda.configuracao');
        Route::get('/agenda/google/auth/{profissional}', [GoogleCalendarController::class, 'authorize'])->name('agenda.google.auth');
        Route::get('/auth/google/callback', [GoogleCalendarController::class, 'callback'])->name('agenda.google.callback');
        Route::post('/agenda/google/desconectar/{profissional}', [GoogleCalendarController::class, 'desconectar'])->name('agenda.google.desconectar');
    });

    Route::middleware('modulo:estoque')->group(function (): void {
        Route::get('/estoque', EstoqueIndex::class)->name('estoque.index');
        Route::get('/estoque/produtos', EstoqueProdutoIndex::class)->name('estoque.produtos');
        Route::get('/estoque/movimentacoes', EstoqueMovimentacaoIndex::class)->name('estoque.movimentacoes');
    });

});
