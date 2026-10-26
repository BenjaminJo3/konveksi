<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'delivery_order_id',
        'quantity_received',
        'discrepancy_note',
        'received_by',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
