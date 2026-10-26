<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrderGroup extends Model
{
    protected $fillable = [
        'number',
        'po_number',
        'source_role',
        'destination_role',
        'goods_type',
        'unit',
        'dispatched_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['dispatched_at' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'delivery_order_group_id')->with(['order', 'receipts.receiver']);
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

    public function totalQuantity(): float
    {
        return (float) $this->items()->sum('quantity');
    }

    public function receivedQuantity(): float
    {
        return (float) $this->items()
            ->join('goods_receipts', 'goods_receipts.delivery_order_id', '=', 'delivery_orders.id')
            ->sum('goods_receipts.quantity_received');
    }

    public function pendingQuantity(): float
    {
        return max(0, $this->totalQuantity() - $this->receivedQuantity());
    }
}
