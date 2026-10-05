<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\StockUnitTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockProduct extends Model
{
    use BelongsToClinica, SoftDeletes;

    protected $table = 'stock_products';

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'unit_type',
        'quantity_per_package',
        'unit_cost',
        'minimum_stock_quantity',
        'beyond_use_hours',
        'requires_lot_control',
        'fornecedor_id',
        'active',
    ];

    protected $casts = [
        'unit_type'               => StockUnitTypeEnum::class,
        'quantity_per_package'    => 'decimal:3',
        'unit_cost'               => 'decimal:2',
        'minimum_stock_quantity'  => 'decimal:3',
        'beyond_use_hours'        => 'integer',
        'requires_lot_control'    => 'boolean',
        'active'                  => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StockCategory::class, 'category_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) $this->batches()
            ->whereIn('status', ['sealed', 'open'])
            ->where('expires_at', '>=', now()->toDateString())
            ->sum('quantity_available');
    }

    public function isLowStock(): bool
    {
        return $this->available_quantity <= (float) $this->minimum_stock_quantity
            && (float) $this->minimum_stock_quantity > 0;
    }
}
