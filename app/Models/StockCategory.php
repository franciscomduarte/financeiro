<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCategory extends Model
{
    protected $table = 'stock_categories';

    protected $fillable = [
        'name',
        'color',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(StockProduct::class, 'category_id');
    }
}
