<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\StockBatchStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    use BelongsToClinica;

    protected $table = 'stock_batches';

    protected $fillable = [
        'product_id',
        'lot_number',
        'manufactured_at',
        'expires_at',
        'quantity_total',
        'quantity_available',
        'purchase_cost',
        'purchased_at',
        'opened_at',
        'beyond_use_expires_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'expires_at'            => 'date',
        'manufactured_at'       => 'date',
        'purchased_at'          => 'date',
        'opened_at'             => 'datetime',
        'beyond_use_expires_at' => 'datetime',
        'quantity_total'        => 'decimal:3',
        'quantity_available'    => 'decimal:3',
        'purchase_cost'         => 'decimal:2',
        'status'                => StockBatchStatusEnum::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(StockProduct::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->whereIn('status', ['sealed', 'open'])
            ->where('expires_at', '>=', now()->toDateString());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeFefo(Builder $query): Builder
    {
        // Open batches first, then sealed; within same status, earliest expiry first
        return $query
            ->orderByRaw("CASE status WHEN 'open' THEN 0 ELSE 1 END")
            ->orderBy('expires_at');
    }

    // ─── Computed helpers ─────────────────────────────────────────

    public function isExpired(): bool
    {
        if ($this->status === StockBatchStatusEnum::Expired) {
            return true;
        }
        if ($this->beyond_use_expires_at && $this->beyond_use_expires_at->isPast()) {
            return true;
        }
        return $this->expires_at->isPast();
    }

    public function isNearExpiry(int $hours = 4): bool
    {
        if (! $this->beyond_use_expires_at || $this->status !== StockBatchStatusEnum::Open) {
            return false;
        }
        return $this->beyond_use_expires_at->diffInMinutes(now()) <= 0
            || $this->beyond_use_expires_at->isBefore(now()->addHours($hours));
    }

    public function beyondUseLabel(): string
    {
        if (! $this->beyond_use_expires_at) {
            return '—';
        }
        if ($this->beyond_use_expires_at->isPast()) {
            return 'VENCIDO (' . $this->beyond_use_expires_at->format('d/m H:i') . ')';
        }
        $diff = now()->diffInMinutes($this->beyond_use_expires_at);
        $h    = intdiv($diff, 60);
        $m    = $diff % 60;
        return "Vence em {$h}h {$m}min ({$this->beyond_use_expires_at->format('H:i')})";
    }
}
