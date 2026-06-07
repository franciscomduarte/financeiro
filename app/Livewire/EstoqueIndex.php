<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\StockBatch;
use App\Models\StockProduct;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EstoqueIndex extends Component
{
    public function render(StockService $stock): View
    {
        $totalProdutos    = StockProduct::active()->count();
        $valorTotalStock  = StockBatch::whereIn('status', ['sealed', 'open'])
            ->where('expires_at', '>=', now()->toDateString())
            ->sum(\Illuminate\Support\Facades\DB::raw('quantity_available * (SELECT unit_cost FROM stock_products WHERE id = stock_batches.product_id)'));

        $produtosAbaixoMinimo = $stock->getLowStockProducts();
        $frascosVencendo      = $stock->getOpenBatchesNearExpiry(hours: 4);
        $frascosVencendo24h   = $stock->getOpenBatchesNearExpiry(hours: 24);

        // Lotes com beyond_use já vencidos (status ainda open — serão expirados no próximo schedule)
        $frascosVencidos = StockBatch::open()
            ->whereNotNull('beyond_use_expires_at')
            ->where('beyond_use_expires_at', '<', now())
            ->with('product:id,name,unit_type')
            ->get();

        return view('livewire.estoque-index', compact(
            'totalProdutos',
            'valorTotalStock',
            'produtosAbaixoMinimo',
            'frascosVencendo',
            'frascosVencendo24h',
            'frascosVencidos',
        ))->layout('layouts.app', ['title' => 'Estoque']);
    }
}
