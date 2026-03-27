<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateContratoAction;
use App\Actions\RegistrarReajusteAction;
use App\Actions\UpdateContratoAction;
use App\Actions\UploadArquivoContratoAction;
use App\Models\Contrato;
use App\Models\Fornecedor;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class ContratoIndex extends Component
{
    use WithPagination;
    use WithFileUploads;

    // ─── Filtros ────────────────────────────────────────────────
    public string $filtroStatus = '';
    public string $filtroRisco  = '';
    public bool   $alertas      = false;

    // ─── Estado dos modais ──────────────────────────────────────
    public bool $modalCriar    = false;
    public bool $modalEditar   = false;
    public bool $modalDetalhe  = false;
    public bool $modalReajuste = false;
    public bool $modalArquivo  = false;

    public ?string $contratoEditandoId = null;
    public ?string $contratoDetalheId  = null;
    public ?string $contratoReajusteId = null;
    public ?string $contratoArquivoId  = null;

    // ─── Formulário do contrato ─────────────────────────────────
    public string $fornecedorId            = '';
    public string $valorMensal             = '';
    public string $dataInicio              = '';
    public string $dataFim                 = '';
    public string $periodicidadeReajuste   = '';
    public string $dataProximoReajuste     = '';
    public string $indiceReajuste          = '';
    public string $multaRescisaoValor      = '';
    public string $multaRescisaoPercentual = '';
    public string $avisoPrevioDias         = '';
    public string $linkContrato            = '';
    public string $risco                   = 'baixo';
    public string $status                  = 'ativo';
    public string $observacoes             = '';

    // ─── Formulário de reajuste ─────────────────────────────────
    public string $dataReajuste        = '';
    public string $valorNovo           = '';
    public string $indiceReajusteOp    = '';
    public string $observacoesReajuste = '';

    // ─── Upload arquivo ─────────────────────────────────────────
    /** @var mixed */
    public $arquivoContrato = null;

    // ─── Flash interno ──────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    protected function queryString(): array
    {
        return [
            'filtroStatus' => ['except' => '', 'as' => 'status'],
            'filtroRisco'  => ['except' => '', 'as' => 'risco'],
            'alertas'      => ['except' => false, 'as' => 'alertas'],
        ];
    }

    public function updatingFiltroStatus(): void { $this->resetPage(); }
    public function updatingFiltroRisco(): void  { $this->resetPage(); }
    public function updatingAlertas(): void      { $this->resetPage(); }

    // ─── Modal Criar ────────────────────────────────────────────
    public function abrirModalCriar(): void
    {
        $this->resetFormulario();
        $this->dataInicio = now()->toDateString();
        $this->modalCriar = true;
    }

    public function salvar(CreateContratoAction $action): void
    {
        $this->validate($this->rules());

        try {
            $action->execute($this->dadosFormulario());
            $this->modalCriar = false;
            $this->resetFormulario();
            $this->flashSucesso = 'Contrato criado com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao criar contrato: ' . $e->getMessage();
        }
    }

    // ─── Modal Editar ───────────────────────────────────────────
    public function abrirModalEditar(string $id): void
    {
        $contrato = Contrato::findOrFail($id);
        $this->contratoEditandoId      = $id;

        $this->fornecedorId            = $contrato->fornecedor_id ?? '';
        $this->valorMensal             = (string) $contrato->getRawOriginal('valor_mensal');
        $this->dataInicio              = $contrato->data_inicio?->toDateString() ?? '';
        $this->dataFim                 = $contrato->data_fim?->toDateString() ?? '';
        $this->periodicidadeReajuste   = $contrato->periodicidade_reajuste?->value ?? '';
        $this->dataProximoReajuste     = $contrato->data_proximo_reajuste?->toDateString() ?? '';
        $this->indiceReajuste          = $contrato->indice_reajuste?->value ?? '';
        $this->multaRescisaoValor      = $contrato->multa_rescisao_valor ? (string) $contrato->getRawOriginal('multa_rescisao_valor') : '';
        $this->multaRescisaoPercentual = $contrato->multa_rescisao_percentual ? (string) $contrato->getRawOriginal('multa_rescisao_percentual') : '';
        $this->avisoPrevioDias         = $contrato->aviso_previo_dias ? (string) $contrato->aviso_previo_dias : '';
        $this->linkContrato            = $contrato->link_contrato ?? '';
        $this->risco                   = $contrato->risco->value;
        $this->status                  = $contrato->status->value;
        $this->observacoes             = $contrato->observacoes ?? '';

        $this->modalEditar = true;
    }

    public function atualizar(UpdateContratoAction $action): void
    {
        $this->validate($this->rules());

        try {
            $contrato = Contrato::findOrFail($this->contratoEditandoId);
            $action->execute($contrato, $this->dadosFormulario());
            $this->modalEditar = false;
            $this->resetFormulario();
            $this->flashSucesso = 'Contrato atualizado com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao atualizar contrato: ' . $e->getMessage();
        }
    }

    // ─── Modal Detalhe ──────────────────────────────────────────
    public function abrirDetalhe(string $id): void
    {
        $this->contratoDetalheId = $id;
        $this->modalDetalhe      = true;
    }

    // ─── Modal Reajuste ─────────────────────────────────────────
    public function abrirModalReajuste(string $id): void
    {
        $this->contratoReajusteId  = $id;
        $this->dataReajuste        = now()->toDateString();
        $this->valorNovo           = '';
        $this->indiceReajusteOp    = '';
        $this->observacoesReajuste = '';
        $this->modalReajuste       = true;
    }

    public function registrarReajuste(RegistrarReajusteAction $action): void
    {
        $this->validate([
            'valorNovo'    => ['required', 'numeric', 'min:0.01'],
            'dataReajuste' => ['required', 'date'],
        ]);

        try {
            $contrato = Contrato::findOrFail($this->contratoReajusteId);
            $action->execute($contrato, [
                'data_reajuste' => $this->dataReajuste,
                'valor_novo'    => (float) $this->valorNovo,
                'indice'        => $this->indiceReajusteOp ?: null,
                'observacoes'   => $this->observacoesReajuste ?: null,
            ]);
            $this->modalReajuste = false;
            $this->flashSucesso  = 'Reajuste registrado com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao registrar reajuste: ' . $e->getMessage();
        }
    }

    // ─── Modal Arquivo ──────────────────────────────────────────
    public function abrirModalArquivo(string $id): void
    {
        $this->contratoArquivoId = $id;
        $this->arquivoContrato   = null;
        $this->modalArquivo      = true;
    }

    public function uploadArquivo(UploadArquivoContratoAction $action): void
    {
        $this->validate([
            'arquivoContrato' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        try {
            $contrato = Contrato::findOrFail($this->contratoArquivoId);
            $action->execute($contrato, $this->arquivoContrato);
            $this->modalArquivo = false;
            $this->flashSucesso = 'Arquivo enviado com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao enviar arquivo: ' . $e->getMessage();
        }
    }

    // ─── Fechar modais ──────────────────────────────────────────
    public function fecharModais(): void
    {
        $this->modalCriar    = false;
        $this->modalEditar   = false;
        $this->modalDetalhe  = false;
        $this->modalReajuste = false;
        $this->modalArquivo  = false;
        $this->resetFormulario();
    }

    // ─── Helpers ────────────────────────────────────────────────
    private function resetFormulario(): void
    {
        $this->fornecedorId            = '';
        $this->valorMensal             = '';
        $this->dataInicio              = '';
        $this->dataFim                 = '';
        $this->periodicidadeReajuste   = '';
        $this->dataProximoReajuste     = '';
        $this->indiceReajuste          = '';
        $this->multaRescisaoValor      = '';
        $this->multaRescisaoPercentual = '';
        $this->avisoPrevioDias         = '';
        $this->linkContrato            = '';
        $this->risco                   = 'baixo';
        $this->status                  = 'ativo';
        $this->observacoes             = '';
        $this->contratoEditandoId      = null;
        $this->contratoDetalheId       = null;
        $this->contratoReajusteId      = null;
        $this->contratoArquivoId       = null;
        $this->arquivoContrato         = null;
        $this->flashSucesso            = null;
        $this->flashErro               = null;
    }

    private function dadosFormulario(): array
    {
        return [
            'fornecedor_id'             => $this->fornecedorId ?: null,
            'valor_mensal'              => (float) $this->valorMensal,
            'data_inicio'               => $this->dataInicio ?: null,
            'data_fim'                  => $this->dataFim ?: null,
            'periodicidade_reajuste'    => $this->periodicidadeReajuste ?: null,
            'data_proximo_reajuste'     => $this->dataProximoReajuste ?: null,
            'indice_reajuste'           => $this->indiceReajuste ?: null,
            'multa_rescisao_valor'      => $this->multaRescisaoValor ? (float) $this->multaRescisaoValor : null,
            'multa_rescisao_percentual' => $this->multaRescisaoPercentual ? (float) $this->multaRescisaoPercentual : null,
            'aviso_previo_dias'         => $this->avisoPrevioDias ? (int) $this->avisoPrevioDias : null,
            'link_contrato'             => $this->linkContrato ?: null,
            'risco'                     => $this->risco,
            'status'                    => $this->status,
            'observacoes'               => $this->observacoes ?: null,
        ];
    }

    private function rules(): array
    {
        return [
            'fornecedorId' => ['nullable', 'string', 'exists:fornecedores,id'],
            'valorMensal'  => ['required', 'numeric', 'min:0'],
            'dataInicio'   => ['nullable', 'date'],
            'dataFim'      => ['nullable', 'date', 'after_or_equal:dataInicio'],
            'risco'        => ['required', 'in:baixo,medio,alto'],
            'status'       => ['required', 'in:ativo,em_negociacao,encerrado,suspenso'],
            'linkContrato' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function render(): View
    {
        // ── Lista paginada ───────────────────────────────────────
        $query = Contrato::query()
            ->with('fornecedor')
            ->orderBy('status')
            ->orderBy('data_fim');

        if ($this->filtroStatus !== '') {
            $query->where('status', $this->filtroStatus);
        }
        if ($this->filtroRisco !== '') {
            $query->where('risco', $this->filtroRisco);
        }
        if ($this->alertas) {
            $query->where(function ($q): void {
                $q->whereNotNull('data_fim')
                  ->where('data_fim', '<=', now()->addDays(60)->toDateString())
                  ->orWhere(function ($q2): void {
                      $q2->whereNotNull('data_proximo_reajuste')
                         ->where('data_proximo_reajuste', '<=', now()->addDays(30)->toDateString());
                  });
            });
        }

        $contratos = $query->paginate(20);

        // ── Stats ────────────────────────────────────────────────
        $ativos      = Contrato::where('status', 'ativo')->count();
        $valorTotal  = (float) Contrato::where('status', 'ativo')->sum('valor_mensal');
        $alertasCount = Contrato::where(function ($q): void {
            $q->whereNotNull('data_fim')
              ->where('data_fim', '<=', now()->addDays(60)->toDateString())
              ->orWhere(function ($q2): void {
                  $q2->whereNotNull('data_proximo_reajuste')
                     ->where('data_proximo_reajuste', '<=', now()->addDays(30)->toDateString());
              });
        })->count();

        // ── Dados para os modais ─────────────────────────────────
        $fornecedoresAtivos = Fornecedor::where('status', 'ativo')
            ->orderBy('nome_fantasia')
            ->get(['id', 'nome_fantasia']);

        $contratoDetalhe = $this->contratoDetalheId
            ? Contrato::with(['fornecedor', 'reajustes'])->find($this->contratoDetalheId)
            : null;

        return view('livewire.contrato-index', [
            'contratos'         => $contratos,
            'ativos'            => $ativos,
            'valorTotal'        => $valorTotal,
            'alertasCount'      => $alertasCount,
            'fornecedoresAtivos' => $fornecedoresAtivos,
            'contratoDetalhe'   => $contratoDetalhe,
        ])->layout('layouts.app', ['title' => 'Contratos']);
    }
}
