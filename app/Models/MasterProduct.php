<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProduct extends Model
{
    protected $table = 'master_products';

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'master_product_id');
    }
}
