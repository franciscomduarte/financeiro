<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StockDailyReportCommand extends Command
{
    protected $signature   = 'stock:daily-report';
    protected $description = 'Relatório diário de estoque: produtos abaixo do mínimo e frascos próximos do vencimento.';

    public function handle(StockService $stock): int
    {
        $lowStock    = $stock->getLowStockProducts();
        $nearExpiry  = $stock->getOpenBatchesNearExpiry(hours: 24);

        if ($lowStock->isNotEmpty()) {
            $this->warn("=== Produtos abaixo do estoque mínimo ({$lowStock->count()}) ===");
            foreach ($lowStock as $product) {
                $this->line("  - {$product->name}: {$product->available_quantity} {$product->unit_type->value} (mínimo: {$product->minimum_stock_quantity})");
            }
            Log::warning('Stock daily report: produtos abaixo do mínimo', [
                'produtos' => $lowStock->pluck('name')->toArray(),
            ]);
        } else {
            $this->info('Todos os produtos acima do estoque mínimo.');
        }

        if ($nearExpiry->isNotEmpty()) {
            $this->warn("=== Frascos abertos vencendo em 24h ({$nearExpiry->count()}) ===");
            foreach ($nearExpiry as $batch) {
                $this->line("  - {$batch->product?->name} | Lote: {$batch->lot_number} | Vence: {$batch->beyond_use_expires_at?->format('d/m H:i')}");
            }
        }

        return Command::SUCCESS;
    }
}
