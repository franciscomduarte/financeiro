<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateFornecedorAction;
use App\Actions\UpdateFornecedorAction;
use App\Models\Fornecedor;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class FornecedorIndex extends Component
{
    use Concerns\MensagemDeErro;
    use WithPagination;

    // ─── Filtros ────────────────────────────────────────────────
    public string $filtroStatus    = '';
    public string $filtroCategoria = '';
    public string $busca           = '';

    // ─── Estado dos modais ──────────────────────────────────────
    public bool $modalCriar   = false;
    public bool $modalEditar  = false;
    public bool $modalDetalhe = false;

    public ?string $fornecedorEditandoId = null;
    public ?string $fornecedorDetalheId  = null;

    // ─── Formulário ─────────────────────────────────────────────
    public string $nomeFantasia              = '';
    public string $razaoSocial               = '';
    public string $cnpj                      = '';
    public string $servicoPrestado           = '';
    public string $categoria                 = '';
    public string $contatoNome               = '';
    public string $contatoTelefone           = '';
    public string $contatoEmail              = '';
    public string $contatoEmergenciaNome     = '';
    public string $contatoEmergenciaTelefone = '';
    public string $status                    = 'ativo';
    public string $observacoes               = '';

    // ─── Flash interno ──────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    protected function queryString(): array
    {
        return [
            'filtroStatus'    => ['except' => '', 'as' => 'status'],
            'filtroCategoria' => ['except' => '', 'as' => 'categoria'],
            'busca'           => ['except' => '', 'as' => 'q'],
        ];
    }

    public function updatingBusca(): void          { $this->resetPage(); }
    public function updatingFiltroStatus(): void   { $this->resetPage(); }
    public function updatingFiltroCategoria(): void { $this->resetPage(); }

    // ─── Modal Criar ────────────────────────────────────────────
    public function abrirModalCriar(): void
    {
        $this->resetFormulario();
        $this->modalCriar = true;
    }

    public function salvar(CreateFornecedorAction $action): void
    {
        $this->validate($this->rules());

        try {
            $action->execute($this->dadosFormulario());
            $this->modalCriar = false;
            $this->resetFormulario();
            $this->flashSucesso = 'Fornecedor salvo.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o fornecedor');
        }
    }

    // ─── Modal Editar ───────────────────────────────────────────
    public function abrirModalEditar(string $id): void
    {
        $fornecedor = Fornecedor::findOrFail($id);
        $this->fornecedorEditandoId = $id;

        $this->nomeFantasia              = $fornecedor->nome_fantasia ?? '';
        $this->razaoSocial               = $fornecedor->razao_social ?? '';
        $this->cnpj                      = $fornecedor->cnpj ?? '';
        $this->servicoPrestado           = $fornecedor->servico_prestado ?? '';
        $this->categoria                 = $fornecedor->categoria ?? '';
        $this->contatoNome               = $fornecedor->contato_nome ?? '';
        $this->contatoTelefone           = $fornecedor->contato_telefone ?? '';
        $this->contatoEmail              = $fornecedor->contato_email ?? '';
        $this->contatoEmergenciaNome     = $fornecedor->contato_emergencia_nome ?? '';
        $this->contatoEmergenciaTelefone = $fornecedor->contato_emergencia_telefone ?? '';
        $this->status                    = $fornecedor->status->value;
        $this->observacoes               = $fornecedor->observacoes ?? '';

        $this->modalEditar = true;
    }

    public function atualizar(UpdateFornecedorAction $action): void
    {
        $this->validate($this->rules());

        try {
            $fornecedor = Fornecedor::findOrFail($this->fornecedorEditandoId);
            $action->execute($fornecedor, $this->dadosFormulario());
            $this->modalEditar = false;
            $this->resetFormulario();
            $this->flashSucesso = 'Alterações salvas.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar as alterações');
        }
    }

    // ─── Modal Detalhe ──────────────────────────────────────────
    public function abrirDetalhe(string $id): void
    {
        $this->fornecedorDetalheId = $id;
        $this->modalDetalhe        = true;
    }

    // ─── Fechar modais ──────────────────────────────────────────
    public function fecharModais(): void
    {
        $this->modalCriar   = false;
        $this->modalEditar  = false;
        $this->modalDetalhe = false;
        $this->resetFormulario();
    }

    // ─── Helpers ────────────────────────────────────────────────
    private function resetFormulario(): void
    {
        $this->nomeFantasia              = '';
        $this->razaoSocial               = '';
        $this->cnpj                      = '';
        $this->servicoPrestado           = '';
        $this->categoria                 = '';
        $this->contatoNome               = '';
        $this->contatoTelefone           = '';
        $this->contatoEmail              = '';
        $this->contatoEmergenciaNome     = '';
        $this->contatoEmergenciaTelefone = '';
        $this->status                    = 'ativo';
        $this->observacoes               = '';
        $this->fornecedorEditandoId      = null;
        $this->fornecedorDetalheId       = null;
        $this->flashSucesso              = null;
        $this->flashErro                 = null;
    }

    private function dadosFormulario(): array
    {
        return [
            'nome_fantasia'               => $this->nomeFantasia,
            'razao_social'                => $this->razaoSocial ?: null,
            'cnpj'                        => $this->cnpj ?: null,
            'servico_prestado'            => $this->servicoPrestado,
            'categoria'                   => $this->categoria ?: null,
            'contato_nome'                => $this->contatoNome ?: null,
            'contato_telefone'            => $this->contatoTelefone ?: null,
            'contato_email'               => $this->contatoEmail ?: null,
            'contato_emergencia_nome'     => $this->contatoEmergenciaNome ?: null,
            'contato_emergencia_telefone' => $this->contatoEmergenciaTelefone ?: null,
            'status'                      => $this->status,
            'observacoes'                 => $this->observacoes ?: null,
        ];
    }

    private function rules(): array
    {
        return [
            'nomeFantasia'    => ['required', 'string', 'max:150'],
            'servicoPrestado' => ['required', 'string', 'max:200'],
            'razaoSocial'     => ['nullable', 'string', 'max:200'],
            'cnpj'            => ['nullable', 'string', 'max:18'],
            'categoria'       => ['nullable', 'string', 'max:100'],
            'contatoNome'     => ['nullable', 'string', 'max:150'],
            'contatoTelefone' => ['nullable', 'string', 'max:20'],
            'contatoEmail'    => ['nullable', 'email', 'max:150'],
            'status'          => ['required', 'in:ativo,suspenso,encerrado'],
            'observacoes'     => ['nullable', 'string'],
        ];
    }

    public function render(): View
    {
        $query = Fornecedor::query()
            ->withCount('contratos')
            ->orderBy('nome_fantasia');

        if ($this->filtroStatus !== '') {
            $query->where('status', $this->filtroStatus);
        }
        if ($this->filtroCategoria !== '') {
            $query->where('categoria', $this->filtroCategoria);
        }
        if ($this->busca !== '') {
            $term = $this->busca;
            $query->where(function ($q) use ($term): void {
                $q->where('nome_fantasia', 'ilike', "%{$term}%")
                  ->orWhere('servico_prestado', 'ilike', "%{$term}%")
                  ->orWhere('cnpj', 'like', "%{$term}%");
            });
        }

        $fornecedores = $query->paginate(20);

        $total      = Fornecedor::count();
        $ativos     = Fornecedor::where('status', 'ativo')->count();
        $categorias = Fornecedor::query()
            ->whereNotNull('categoria')->where('categoria', '!=', '')
            ->distinct()->orderBy('categoria')->pluck('categoria');

        $fornecedorDetalhe = $this->fornecedorDetalheId
            ? Fornecedor::with('contratos')->withCount('contratos')->find($this->fornecedorDetalheId)
            : null;

        return view('livewire.fornecedor-index', [
            'fornecedores'      => $fornecedores,
            'totalCount'        => $total,
            'ativosCount'       => $ativos,
            'categoriasList'    => $categorias,
            'fornecedorDetalhe' => $fornecedorDetalhe,
        ])->layout('layouts.app', ['title' => 'Fornecedores']);
    }
}
