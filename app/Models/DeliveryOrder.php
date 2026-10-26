<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'delivery_order_group_id',
        'number',
        'production_order_id',
        'source_role',
        'destination_role',
        'goods_type',
        'quantity',
        'unit',
        'dispatched_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'dispatched_at' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderGroup::class, 'delivery_order_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class)->orderByDesc('received_at');
    }

    public function receivedQuantity(): float
    {
        return (float) $this->receipts()->sum('quantity_received');
    }

    public function pendingQuantity(): float
    {
        return max(0, (float) $this->quantity - $this->receivedQuantity());
    }

    public function destinationLabel(): string
    {
        return (new User(['role' => $this->destination_role]))->roleLabel();
    }

    public function sourceLabel(): string
    {
        return (new User(['role' => $this->source_role]))->roleLabel();
    }

    public function goodsLabel(): string
    {
        return [
            'fabric' => 'Kain',
            'accessories' => 'Aksesoris',
            'cut_pieces' => 'Potongan bahan',
            'finished_goods' => 'Baju jadi',
        ][$this->goods_type] ?? 'Barang';
    }
}
