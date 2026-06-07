<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;

class CheckExpiredBatchesCommand extends Command
{
    protected $signature   = 'stock:check-expired-batches';
    protected $description = 'Expira frascos abertos cujo beyond-use date passou e registra movimento de descarte.';

    public function handle(StockService $stock): int
    {
        $count = $stock->expireOpenBatches();

        if ($count > 0) {
            $this->warn("{$count} frasco(s) expirado(s) e descartado(s).");
        } else {
            $this->info('Nenhum frasco aberto vencido.');
        }

        return Command::SUCCESS;
    }
}
