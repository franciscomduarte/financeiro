<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateContaConsumoAction;
use App\Actions\LancarFaturaAction;
use App\Actions\PagarFaturaAction;
use App\Actions\UpdateContaConsumoAction;
use App\Enums\StatusFatura;
use App\Enums\TipoContaConsumo;
use App\Models\ContaConsumo;
use App\Models\ContaConsumoFatura;
use App\Models\Fornecedor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class ContaConsumoIndex extends Component
{
    use WithPagination;

    // ─── Filtros ────────────────────────────────────────────────
    public string $filtroStatus   = '';
    public string $filtroContaId  = '';

    // ─── Estado dos modais ──────────────────────────────────────
    public bool $modalCriar   = false;
    public bool $modalEditar  = false;
    public bool $modalFatura  = false;
    public bool $modalPagar   = false;

    public ?string $contaEditandoId = null;
    public ?string $contaFaturaId   = null;
    public ?string $faturaId        = null;

    // ─── Formulário Conta ───────────────────────────────────────
    public string $tipo           = 'agua';
    public string $fornecedorId   = '';
    public string $descricao      = '';
    public string $diaVencimento  = '';
    public string $valorEstimado  = '';
    public string $statusConta    = 'ativo';
    public string $observacoes    = '';

    // ─── Formulário Fatura ──────────────────────────────────────
    public string $competencia     = '';
    public string $dataVencimento  = '';
    public string $valor           = '';
    public string $consumoChave    = '';
    public string $consumoValor    = '';
    public string $faturaObs       = '';

    // ─── Formulário Pagar ───────────────────────────────────────
    public string $formaPagamento  = 'pix';
    public string $dataPagamento   = '';
    public string $valorPagamento  = '';

    // ─── Flash ──────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    protected function queryString(): array
    {
        return [
            'filtroStatus'  => ['except' => '', 'as' => 'status'],
            'filtroContaId' => ['except' => '', 'as' => 'conta'],
        ];
    }

    public function updatingFiltroStatus(): void  { $this->resetPage(); }
    public function updatingFiltroContaId(): void { $this->resetPage(); }

    // ─── Modal Criar Conta ──────────────────────────────────────
    public function abrirModalCriar(): void
    {
        $this->resetFormularioConta();
        $this->modalCriar = true;
    }

    public function salvar(CreateContaConsumoAction $action): void
    {
        $this->validate($this->rulesConta());

        try {
            $action->execute($this->dadosConta());
            $this->modalCriar = false;
            $this->resetFormularioConta();
            $this->flashSucesso = 'Conta cadastrada com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao cadastrar conta: ' . $e->getMessage();
        }
    }

    // ─── Modal Editar Conta ─────────────────────────────────────
    public function abrirModalEditar(string $id): void
    {
        $conta = ContaConsumo::findOrFail($id);
        $this->contaEditandoId = $id;
        $this->tipo            = $conta->tipo->value;
        $this->fornecedorId    = $conta->fornecedor_id ?? '';
        $this->descricao       = $conta->descricao;
        $this->diaVencimento   = (string) $conta->dia_vencimento;
        $this->valorEstimado   = $conta->valor_estimado ? (string) $conta->getRawOriginal('valor_estimado') : '';
        $this->statusConta     = $conta->status;
        $this->observacoes     = $conta->observacoes ?? '';
        $this->modalEditar     = true;
    }

    public function atualizar(UpdateContaConsumoAction $action): void
    {
        $this->validate($this->rulesConta());

        try {
            $conta = ContaConsumo::findOrFail($this->contaEditandoId);
            $action->execute($conta, $this->dadosConta());
            $this->modalEditar = false;
            $this->resetFormularioConta();
            $this->flashSucesso = 'Conta atualizada com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao atualizar conta: ' . $e->getMessage();
        }
    }

    // ─── Modal Lançar Fatura ────────────────────────────────────
    public function abrirModalFatura(string $contaId): void
    {
        $conta = ContaConsumo::findOrFail($contaId);
        $this->contaFaturaId   = $contaId;
        $this->competencia     = now()->format('Y-m');
        $this->dataVencimento  = now()->day($conta->dia_vencimento)->format('Y-m-d');
        $this->valor           = '';
        $this->consumoChave    = $conta->tipo->consumoChave() ?? '';
        $this->consumoValor    = '';
        $this->faturaObs       = '';
        $this->modalFatura     = true;
    }

    public function lancarFatura(LancarFaturaAction $action): void
    {
        $this->validate([
            'competencia'    => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'dataVencimento' => ['required', 'date'],
            'valor'          => ['nullable', 'numeric', 'min:0.01'],
            'consumoValor'   => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $conta = ContaConsumo::findOrFail($this->contaFaturaId);
            $action->execute($conta, [
                'competencia'    => $this->competencia,
                'data_vencimento'=> $this->dataVencimento,
                'valor'          => $this->valor ?: null,
                'consumo_chave'  => $this->consumoChave ?: null,
                'consumo_valor'  => $this->consumoValor ?: null,
                'observacoes'    => $this->faturaObs ?: null,
            ]);
            $this->modalFatura  = false;
            $this->flashSucesso = 'Fatura lançada com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao lançar fatura: ' . $e->getMessage();
        }
    }

    // ─── Modal Pagar Fatura ─────────────────────────────────────
    public function abrirModalPagar(string $id): void
    {
        $fatura = ContaConsumoFatura::findOrFail($id);
        $this->faturaId        = $id;
        $this->valorPagamento  = $fatura->valor ? (string) $fatura->getRawOriginal('valor') : '';
        $this->formaPagamento  = 'pix';
        $this->dataPagamento   = now()->toDateString();
        $this->modalPagar      = true;
    }

    public function pagar(PagarFaturaAction $action): void
    {
        $this->validate([
            'valorPagamento' => ['required', 'numeric', 'min:0.01'],
            'formaPagamento' => ['required', 'string'],
            'dataPagamento'  => ['required', 'date'],
        ]);

        try {
            $fatura = ContaConsumoFatura::findOrFail($this->faturaId);
            $action->execute($fatura, [
                'valor'           => $this->valorPagamento,
                'forma_pagamento' => $this->formaPagamento,
                'data_pagamento'  => $this->dataPagamento,
            ]);
            $this->modalPagar   = false;
            $this->flashSucesso = 'Fatura paga e lançada no financeiro!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao registrar pagamento: ' . $e->getMessage();
        }
    }

    // ─── Fechar modais ──────────────────────────────────────────
    public function fecharModais(): void
    {
        $this->modalCriar     = false;
        $this->modalEditar    = false;
        $this->modalFatura    = false;
        $this->modalPagar     = false;
        $this->flashErro      = null;
        $this->resetFormularioConta();
    }

    // ─── Helpers ────────────────────────────────────────────────
    private function resetFormularioConta(): void
    {
        $this->tipo            = 'agua';
        $this->fornecedorId    = '';
        $this->descricao       = '';
        $this->diaVencimento   = '';
        $this->valorEstimado   = '';
        $this->statusConta     = 'ativo';
        $this->observacoes     = '';
        $this->contaEditandoId = null;
        $this->contaFaturaId   = null;
        $this->faturaId        = null;
    }

    private function dadosConta(): array
    {
        return [
            'tipo'           => $this->tipo,
            'fornecedor_id'  => $this->fornecedorId ?: null,
            'descricao'      => $this->descricao,
            'dia_vencimento' => (int) $this->diaVencimento,
            'valor_estimado' => $this->valorEstimado ? (float) $this->valorEstimado : null,
            'status'         => $this->statusConta,
            'observacoes'    => $this->observacoes ?: null,
        ];
    }

    private function rulesConta(): array
    {
        return [
            'tipo'          => ['required', 'in:agua,luz,gas,telefone,internet,outro'],
            'descricao'     => ['required', 'string', 'max:255'],
            'diaVencimento' => ['required', 'integer', 'min:1', 'max:31'],
            'valorEstimado' => ['nullable', 'numeric', 'min:0'],
            'fornecedorId'  => ['nullable', 'exists:fornecedores,id'],
            'statusConta'   => ['required', 'in:ativo,inativo'],
        ];
    }

    public function render(): View
    {
        $contas = ContaConsumo::query()
            ->with(['fornecedor', 'ultimaFatura'])
            ->orderBy('tipo')
            ->orderBy('descricao')
            ->get();

        $faturas = ContaConsumoFatura::query()
            ->with('contaConsumo.fornecedor')
            ->when($this->filtroStatus !== '', fn ($q) => $q->where('status', $this->filtroStatus))
            ->when($this->filtroContaId !== '', fn ($q) => $q->where('conta_consumo_id', $this->filtroContaId))
            ->orderByDesc('competencia')
            ->orderBy('data_vencimento')
            ->paginate(20);

        $mesAtual       = now()->format('Y-m');
        $totalPendente  = ContaConsumoFatura::whereIn('status', ['pendente', 'recebida'])->sum('valor');
        $totalVencidas  = ContaConsumoFatura::where('status', 'vencida')->count();
        $totalPagoMes   = ContaConsumoFatura::where('status', 'paga')
            ->where('competencia', $mesAtual)
            ->sum('valor');

        $fornecedoresAtivos = Fornecedor::where('status', 'ativo')
            ->orderBy('nome_fantasia')
            ->get(['id', 'nome_fantasia']);

        $faturaParaPagar = $this->faturaId
            ? ContaConsumoFatura::with('contaConsumo')->find($this->faturaId)
            : null;

        return view('livewire.conta-consumo-index', [
            'contas'             => $contas,
            'faturas'            => $faturas,
            'totalPendente'      => (float) $totalPendente,
            'totalVencidas'      => $totalVencidas,
            'totalPagoMes'       => (float) $totalPagoMes,
            'fornecedoresAtivos' => $fornecedoresAtivos,
            'faturaParaPagar'    => $faturaParaPagar,
            'tipos'              => TipoContaConsumo::cases(),
        ])->layout('layouts.app', ['title' => 'Contas de Consumo']);
    }
}
