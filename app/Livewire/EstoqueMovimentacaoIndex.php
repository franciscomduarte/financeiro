<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\StockMovementTypeEnum;
use App\Models\StockMovement;
use App\Models\StockProduct;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EstoqueMovimentacaoIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'prod', except: '')]
    public string $filtroProduto = '';

    #[Url(as: 'tipo', except: '')]
    public string $filtroTipo = '';

    #[Url(as: 'mes', except: '')]
    public string $filtroMes = '';

    public function mount(): void
    {
        if ($this->filtroMes === '') {
            $this->filtroMes = now()->format('Y-m');
        }
    }

    public function updatingBusca(): void       { $this->resetPage(); }
    public function updatingFiltroProduto(): void { $this->resetPage(); }
    public function updatingFiltroTipo(): void   { $this->resetPage(); }
    public function updatingFiltroMes(): void    { $this->resetPage(); }

    public function render(): View
    {
        $query = StockMovement::with([
            'product:id,name,unit_type',
            'batch:id,lot_number',
            'paciente:id,nome',
            'performedBy:id,name',
        ])->orderByDesc('created_at');

        if ($this->busca !== '') {
            $term = $this->busca;
            $query->where(function ($q) use ($term): void {
                $q->whereHas('paciente', fn ($q2) => $q2->where('nome', 'ilike', "%{$term}%"))
                  ->orWhereHas('product', fn ($q2) => $q2->where('name', 'ilike', "%{$term}%"))
                  ->orWhere('procedure_name', 'ilike', "%{$term}%");
            });
        }

        if ($this->filtroProduto !== '') {
            $query->where('product_id', $this->filtroProduto);
        }

        if ($this->filtroTipo !== '') {
            $query->where('type', $this->filtroTipo);
        }

        if ($this->filtroMes !== '') {
            $query->whereRaw("TO_CHAR(created_at, 'YYYY-MM') = ?", [$this->filtroMes]);
        }

        $movimentacoes = $query->paginate(30);
        $produtos       = StockProduct::active()->orderBy('name')->get(['id', 'name']);
        $tiposMovimento = StockMovementTypeEnum::cases();

        return view('livewire.estoque-movimentacao-index', compact(
            'movimentacoes',
            'produtos',
            'tiposMovimento',
        ))->layout('layouts.app', ['title' => 'Movimentações — Estoque']);
    }
}
