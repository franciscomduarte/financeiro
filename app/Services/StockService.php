<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockBatchStatusEnum;
use App\Enums\StockMovementTypeEnum;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StockService
{
    // ─── Entrada de compra ────────────────────────────────────────

    public function purchaseEntry(
        int $productId,
        string $lotNumber,
        float $quantity,
        float $cost,
        string $expiresAt,
        string $purchasedAt,
        ?int $fornecedorId = null,
        ?string $notes = null,
    ): StockBatch {
        return DB::transaction(function () use ($productId, $lotNumber, $quantity, $cost, $expiresAt, $purchasedAt, $notes): StockBatch {
            $batch = StockBatch::create([
                'product_id'         => $productId,
                'lot_number'         => $lotNumber ?: null,
                'expires_at'         => $expiresAt,
                'quantity_total'     => $quantity,
                'quantity_available' => $quantity,
                'purchase_cost'      => $cost,
                'purchased_at'       => $purchasedAt,
                'status'             => StockBatchStatusEnum::Sealed->value,
                'notes'              => $notes,
            ]);

            StockMovement::create([
                'batch_id'          => $batch->id,
                'product_id'        => $productId,
                'type'              => StockMovementTypeEnum::Purchase->value,
                'quantity'          => $quantity,
                'unit_cost_at_time' => $quantity > 0 ? round($cost / $quantity, 4) : 0,
                'created_at'        => now(),
            ]);

            Log::info("Stock: entrada de compra — produto #{$productId}, lote {$lotNumber}, qty {$quantity}");

            return $batch;
        });
    }

    // ─── Abrir frasco manualmente ─────────────────────────────────

    public function openBatch(StockBatch $batch): StockBatch
    {
        if ($batch->status !== StockBatchStatusEnum::Sealed) {
            throw new RuntimeException("Apenas lotes com status 'lacrado' podem ser abertos.");
        }

        $openedAt       = now();
        $beyondUseExp   = null;

        $beyondUseHours = $batch->product?->beyond_use_hours;
        if ($beyondUseHours) {
            $beyondUseExp = $openedAt->copy()->addHours($beyondUseHours);
        }

        $batch->update([
            'opened_at'             => $openedAt,
            'beyond_use_expires_at' => $beyondUseExp,
            'status'                => StockBatchStatusEnum::Open->value,
        ]);

        Log::info("Stock: frasco aberto — batch #{$batch->id}, beyond_use_expires_at: " . ($beyondUseExp?->toDateTimeString() ?? 'n/a'));

        return $batch->fresh();
    }

    // ─── Consumo em procedimento (FEFO) ───────────────────────────

    /**
     * Consome `$quantityNeeded` de um produto, aplicando FEFO.
     * Retorna array com os lotes consumidos e quantidades.
     * @return array{batch_id: int, quantity: float}[]
     */
    public function consumeForProcedure(
        int $productId,
        float $quantityNeeded,
        ?string $procedureName = null,
        ?string $pacienteId = null,
        ?int $performedBy = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($productId, $quantityNeeded, $procedureName, $pacienteId, $performedBy, $notes): array {
            $this->validateAvailable($productId, $quantityNeeded);

            $batches    = StockBatch::where('product_id', $productId)->available()->fefo()->lockForUpdate()->get();
            $remaining  = $quantityNeeded;
            $consumed   = [];
            $product    = StockProduct::findOrFail($productId);

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                // Abre frasco se ainda estiver lacrado
                if ($batch->status === StockBatchStatusEnum::Sealed) {
                    $this->openBatch($batch);
                    $batch->refresh();
                }

                $use       = min($remaining, (float) $batch->quantity_available);
                $remaining -= $use;

                $batch->quantity_available = (float) $batch->quantity_available - $use;
                $batch->status             = $batch->quantity_available <= 0
                    ? StockBatchStatusEnum::Empty->value
                    : $batch->status->value;
                $batch->save();

                StockMovement::create([
                    'batch_id'          => $batch->id,
                    'product_id'        => $productId,
                    'type'              => StockMovementTypeEnum::ProcedureUse->value,
                    'quantity'          => -$use,
                    'unit_cost_at_time' => $product->unit_cost,
                    'paciente_id'       => $pacienteId,
                    'procedure_name'    => $procedureName,
                    'performed_by'      => $performedBy,
                    'notes'             => $notes,
                    'created_at'        => now(),
                ]);

                $consumed[] = ['batch_id' => $batch->id, 'quantity' => $use];
            }

            return $consumed;
        });
    }

    // ─── Saída manual (descarte, ajuste) ─────────────────────────

    public function manualExit(
        int $batchId,
        float $quantity,
        StockMovementTypeEnum $type = StockMovementTypeEnum::ManualExit,
        ?string $pacienteId = null,
        ?string $procedureName = null,
        ?int $performedBy = null,
        ?string $notes = null,
    ): StockMovement {
        return DB::transaction(function () use ($batchId, $quantity, $type, $pacienteId, $procedureName, $performedBy, $notes): StockMovement {
            $batch = StockBatch::lockForUpdate()->findOrFail($batchId);

            if ((float) $batch->quantity_available < $quantity) {
                throw new RuntimeException("Saldo insuficiente. Disponível: {$batch->quantity_available}");
            }

            $batch->quantity_available = (float) $batch->quantity_available - $quantity;
            if ($batch->quantity_available <= 0) {
                $batch->status = StockBatchStatusEnum::Empty->value;
            }
            $batch->save();

            return StockMovement::create([
                'batch_id'          => $batch->id,
                'product_id'        => $batch->product_id,
                'type'              => $type->value,
                'quantity'          => -$quantity,
                'unit_cost_at_time' => $batch->product?->unit_cost,
                'paciente_id'       => $pacienteId,
                'procedure_name'    => $procedureName,
                'performed_by'      => $performedBy,
                'notes'             => $notes,
                'created_at'        => now(),
            ]);
        });
    }

    // ─── Expirar lotes abertos vencidos ──────────────────────────

    public function expireOpenBatches(): int
    {
        $expired = StockBatch::open()
            ->whereNotNull('beyond_use_expires_at')
            ->where('beyond_use_expires_at', '<', now())
            ->get();

        foreach ($expired as $batch) {
            DB::transaction(function () use ($batch): void {
                $saldo = (float) $batch->quantity_available;

                if ($saldo > 0) {
                    StockMovement::create([
                        'batch_id'    => $batch->id,
                        'product_id'  => $batch->product_id,
                        'type'        => StockMovementTypeEnum::DiscardExpired->value,
                        'quantity'    => -$saldo,
                        'notes'       => 'Descarte automático — frasco aberto vencido (beyond-use date)',
                        'created_at'  => now(),
                    ]);
                }

                $batch->update([
                    'quantity_available' => 0,
                    'status'             => StockBatchStatusEnum::Expired->value,
                ]);
            });
        }

        if ($expired->count() > 0) {
            Log::warning("Stock: {$expired->count()} frasco(s) aberto(s) expirado(s) descartados.");
        }

        return $expired->count();
    }

    // ─── Consultas ────────────────────────────────────────────────

    public function getAvailableQuantity(int $productId): float
    {
        return (float) StockBatch::where('product_id', $productId)
            ->available()
            ->sum('quantity_available');
    }

    public function getLowStockProducts(): Collection
    {
        return StockProduct::active()
            ->with('category:id,name,color')
            ->get()
            ->filter(fn (StockProduct $p) => $p->isLowStock());
    }

    public function getOpenBatchesNearExpiry(int $hours = 4): Collection
    {
        return StockBatch::open()
            ->with('product:id,name,unit_type,category_id')
            ->whereNotNull('beyond_use_expires_at')
            ->where('beyond_use_expires_at', '<=', now()->addHours($hours))
            ->orderBy('beyond_use_expires_at')
            ->get();
    }

    // ─── Helpers privados ─────────────────────────────────────────

    private function validateAvailable(int $productId, float $quantityNeeded): void
    {
        $available = $this->getAvailableQuantity($productId);
        if ($available < $quantityNeeded) {
            throw new RuntimeException("Estoque insuficiente. Disponível: {$available}");
        }
    }
}
