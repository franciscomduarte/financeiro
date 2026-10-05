<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\StockMovementTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToClinica;

    protected $table = 'stock_movements';

    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'product_id',
        'type',
        'quantity',
        'unit_cost_at_time',
        'paciente_id',
        'procedure_name',
        'performed_by',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'type'              => StockMovementTypeEnum::class,
        'quantity'          => 'decimal:3',
        'unit_cost_at_time' => 'decimal:2',
        'created_at'        => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StockProduct::class, 'product_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
