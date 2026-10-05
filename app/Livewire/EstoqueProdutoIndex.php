<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\StockBatchStatusEnum;
use App\Enums\StockMovementTypeEnum;
use App\Enums\StockUnitTypeEnum;
use App\Models\Paciente;
use App\Models\StockBatch;
use App\Models\StockCategory;
use App\Models\StockProduct;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class EstoqueProdutoIndex extends Component
{
    use WithPagination;

    // ─── Filtros ─────────────────────────────────────────────────
    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'cat', except: '')]
    public string $filtroCategoria = '';

    #[Url(as: 'st', except: '')]
    public string $filtroStatus = ''; // ok | baixo | critico

    // ─── Painel de lotes ─────────────────────────────────────────
    public ?int $produtoSelecionadoId = null;

    // ─── Modal: novo/editar produto ───────────────────────────────
    public bool   $modalProduto       = false;
    public ?int   $produtoEditandoId  = null;
    public string $formNome           = '';
    public string $formDescricao      = '';
    public string $formCategoriaId    = '';
    public string $formUnitType       = 'unidade';
    public string $formQtdPorPacote   = '1';
    public string $formCustoUnitario  = '';
    public string $formEstoqueMinimo  = '';
    public string $formBeyondUseHours = '';
    public bool   $formRequerLote     = true;
    public bool   $formAtivo          = true;

    // ─── Modal: entrada de compra ─────────────────────────────────
    public bool   $modalEntrada         = false;
    public ?int   $entradaProdutoId     = null;
    public string $entradaLoteNumero    = '';
    public string $entradaQuantidade    = '';
    public string $entradaCusto         = '';
    public string $entradaValidade      = '';
    public string $entradaDataCompra    = '';

    // ─── Modal: consumo manual ────────────────────────────────────
    public bool   $modalConsumo         = false;
    public ?int   $consumoBatchId       = null;
    public string $consumoQuantidade    = '';
    public string $consumoPacienteId    = '';
    public string $consumoProcedimento  = '';
    public string $consumoMotivo        = 'manual_exit';
    public string $consumoNotes         = '';

    // ─── Modal: abrir frasco ─────────────────────────────────────
    public bool $modalAbrirFrasco = false;
    public ?int $abrirFrascoBatchId = null;

    // ─── Flash ───────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    // ─── Resetar paginação ────────────────────────────────────────
    public function updatingBusca(): void          { $this->resetPage(); }
    public function updatingFiltroCategoria(): void { $this->resetPage(); }
    public function updatingFiltroStatus(): void    { $this->resetPage(); }

    // ═══════════════════════════════════════════════════════════════
    // PAINEL DE LOTES
    // ═══════════════════════════════════════════════════════════════

    public function selecionarProduto(?int $id): void
    {
        $this->produtoSelecionadoId = $this->produtoSelecionadoId === $id ? null : $id;
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUTO CRUD
    // ═══════════════════════════════════════════════════════════════

    public function abrirModalNovoProduto(): void
    {
        $this->produtoEditandoId  = null;
        $this->formNome           = '';
        $this->formDescricao      = '';
        $this->formCategoriaId    = '';
        $this->formUnitType       = 'unidade';
        $this->formQtdPorPacote   = '1';
        $this->formCustoUnitario  = '';
        $this->formEstoqueMinimo  = '0';
        $this->formBeyondUseHours = '';
        $this->formRequerLote     = true;
        $this->formAtivo          = true;
        $this->resetValidation();
        $this->modalProduto = true;
    }

    public function abrirModalEditarProduto(int $id): void
    {
        $produto = StockProduct::findOrFail($id);
        $this->produtoEditandoId  = $id;
        $this->formNome           = $produto->name;
        $this->formDescricao      = $produto->description ?? '';
        $this->formCategoriaId    = (string) $produto->category_id;
        $this->formUnitType       = $produto->unit_type->value;
        $this->formQtdPorPacote   = (string) $produto->quantity_per_package;
        $this->formCustoUnitario  = (string) $produto->unit_cost;
        $this->formEstoqueMinimo  = (string) $produto->minimum_stock_quantity;
        $this->formBeyondUseHours = $produto->beyond_use_hours ? (string) $produto->beyond_use_hours : '';
        $this->formRequerLote     = $produto->requires_lot_control;
        $this->formAtivo          = $produto->active;
        $this->resetValidation();
        $this->modalProduto = true;
    }

    public function salvarProduto(): void
    {
        $dados = $this->validate([
            'formNome'           => 'required|string|max:200',
            'formCategoriaId'    => 'required|exists:stock_categories,id',
            'formUnitType'       => 'required|in:' . implode(',', array_column(StockUnitTypeEnum::cases(), 'value')),
            'formQtdPorPacote'   => 'required|numeric|min:0.001',
            'formCustoUnitario'  => 'required|numeric|min:0',
            'formEstoqueMinimo'  => 'required|numeric|min:0',
            'formBeyondUseHours' => 'nullable|integer|min:1|max:9999',
        ]);

        try {
            $payload = [
                'name'                   => $dados['formNome'],
                'description'            => $this->formDescricao ?: null,
                'category_id'            => (int) $dados['formCategoriaId'],
                'unit_type'              => $dados['formUnitType'],
                'quantity_per_package'   => (float) $dados['formQtdPorPacote'],
                'unit_cost'              => (float) $dados['formCustoUnitario'],
                'minimum_stock_quantity' => (float) $dados['formEstoqueMinimo'],
                'beyond_use_hours'       => $this->formBeyondUseHours !== '' ? (int) $this->formBeyondUseHours : null,
                'requires_lot_control'   => $this->formRequerLote,
                'active'                 => $this->formAtivo,
            ];

            if ($this->produtoEditandoId) {
                StockProduct::findOrFail($this->produtoEditandoId)->update($payload);
                $this->flashSucesso = 'Produto salvo.';
            } else {
                StockProduct::create($payload);
                $this->flashSucesso = 'Produto cadastrado.';
            }

            $this->modalProduto = false;
        } catch (Throwable $e) {
            report($e);
            $this->flashErro = 'Não foi possível salvar o produto. Tente de novo em instantes.';
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // ENTRADA DE COMPRA
    // ═══════════════════════════════════════════════════════════════

    public function abrirModalEntrada(?int $produtoId = null): void
    {
        $this->entradaProdutoId  = $produtoId;
        $this->entradaLoteNumero = '';
        $this->entradaQuantidade = '';
        $this->entradaCusto      = '';
        $this->entradaValidade   = '';
        $this->entradaDataCompra = now()->format('Y-m-d');
        $this->resetValidation();
        $this->modalEntrada = true;
    }

    public function salvarEntrada(StockService $stock): void
    {
        $dados = $this->validate([
            'entradaProdutoId'  => 'required|exists:stock_products,id',
            'entradaQuantidade' => 'required|numeric|min:0.001',
            'entradaCusto'      => 'required|numeric|min:0',
            'entradaValidade'   => 'required|date|after:today',
            'entradaDataCompra' => 'required|date',
        ]);

        try {
            $stock->purchaseEntry(
                productId:   (int) $dados['entradaProdutoId'],
                lotNumber:   $this->entradaLoteNumero,
                quantity:    (float) $dados['entradaQuantidade'],
                cost:        (float) $dados['entradaCusto'],
                expiresAt:   $dados['entradaValidade'],
                purchasedAt: $dados['entradaDataCompra'],
            );

            $this->modalEntrada = false;

            // Manter o painel do produto aberto
            if ($this->entradaProdutoId) {
                $this->produtoSelecionadoId = (int) $this->entradaProdutoId;
            }

            $this->flashSucesso = 'Entrada registrada.';
        } catch (Throwable $e) {
            $this->flashErro = 'Não foi possível registrar a entrada: ' . $e->getMessage();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // ABRIR FRASCO
    // ═══════════════════════════════════════════════════════════════

    public function abrirModalAbrirFrasco(int $batchId): void
    {
        $this->abrirFrascoBatchId = $batchId;
        $this->modalAbrirFrasco   = true;
    }

    public function confirmarAbrirFrasco(StockService $stock): void
    {
        try {
            $batch = StockBatch::with('product')->findOrFail($this->abrirFrascoBatchId);
            $stock->openBatch($batch);
            $this->modalAbrirFrasco = false;
            $this->flashSucesso     = "Frasco aberto. " .
                ($batch->product?->beyond_use_hours
                    ? "Válido por {$batch->product->beyond_use_hours}h."
                    : "Sem restrição de validade pós-abertura.");
        } catch (Throwable $e) {
            $this->flashErro = $e->getMessage();
            $this->modalAbrirFrasco = false;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CONSUMO MANUAL
    // ═══════════════════════════════════════════════════════════════

    public function abrirModalConsumo(int $batchId): void
    {
        $this->consumoBatchId      = $batchId;
        $this->consumoQuantidade   = '';
        $this->consumoPacienteId   = '';
        $this->consumoProcedimento = '';
        $this->consumoMotivo       = StockMovementTypeEnum::ManualExit->value;
        $this->consumoNotes        = '';
        $this->resetValidation();
        $this->modalConsumo = true;
    }

    public function salvarConsumo(StockService $stock): void
    {
        $dados = $this->validate([
            'consumoBatchId'    => 'required|exists:stock_batches,id',
            'consumoQuantidade' => 'required|numeric|min:0.001',
            'consumoMotivo'     => 'required|in:' . implode(',', array_column(StockMovementTypeEnum::cases(), 'value')),
        ]);

        try {
            $stock->manualExit(
                batchId:       (int) $dados['consumoBatchId'],
                quantity:      (float) $dados['consumoQuantidade'],
                type:          StockMovementTypeEnum::from($dados['consumoMotivo']),
                pacienteId:    $this->consumoPacienteId ?: null,
                procedureName: $this->consumoProcedimento ?: null,
                performedBy:   auth()->id(),
                notes:         $this->consumoNotes ?: null,
            );

            $this->modalConsumo = false;
            $this->flashSucesso = 'Uso registrado.';
        } catch (Throwable $e) {
            $this->flashErro = $e->getMessage();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // UTILITÁRIOS
    // ═══════════════════════════════════════════════════════════════

    public function fecharModais(): void
    {
        $this->modalProduto     = false;
        $this->modalEntrada     = false;
        $this->modalConsumo     = false;
        $this->modalAbrirFrasco = false;
    }

    public function render(): View
    {
        $query = StockProduct::with('category:id,name,color')
            ->active()
            ->orderBy('name');

        if ($this->busca !== '') {
            $term = $this->busca;
            $query->where('name', 'ilike', "%{$term}%");
        }

        if ($this->filtroCategoria !== '') {
            $query->where('category_id', $this->filtroCategoria);
        }

        $produtos = $query->paginate(20);

        // Para o status de cada produto, precisamos do saldo (eager load batches count)
        $produtos->each(function (StockProduct $p): void {
            $p->append('available_quantity');
        });

        // Lotes do produto selecionado
        $lotesSelecionados = collect();
        if ($this->produtoSelecionadoId) {
            $lotesSelecionados = StockBatch::where('product_id', $this->produtoSelecionadoId)
                ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'sealed' THEN 1 WHEN 'empty' THEN 2 ELSE 3 END")
                ->orderBy('expires_at')
                ->get();
        }

        $categories     = StockCategory::orderBy('name')->get(['id', 'name', 'color']);
        $unitTypes      = StockUnitTypeEnum::cases();
        $movementTypes  = StockMovementTypeEnum::cases();
        $pacientesSelect = Paciente::where('status', 'ativo')->select(['id', 'nome'])->orderBy('nome')->get();

        return view('livewire.estoque-produto-index', compact(
            'produtos',
            'lotesSelecionados',
            'categories',
            'unitTypes',
            'movementTypes',
            'pacientesSelect',
        ))->layout('layouts.app', ['title' => 'Produtos']);
    }
}
