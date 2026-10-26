<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    protected $fillable = [
        'po_number',
        'po_date',
        'category',
        'sub_category',
        'model',
        'size',
        'product_name',
        'color',
        'fabric_name',
        'order_quantity',
        'fabric_estimate',
        'fabric_ratio',
        'yard_kg',
        'rib_qty',
        'ratio',
        'ratio_result_qty',
        'fabric_delivered',
        'spk_quantity',
        'status',
        'delivery_due_at',
        'close_note',
        'closed_at',
        'created_by',
        'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'fabric_estimate' => 'decimal:2',
            'fabric_ratio' => 'decimal:3',
            'po_date' => 'date',
            'yard_kg' => 'decimal:2',
            'rib_qty' => 'decimal:2',
            'ratio' => 'decimal:4',
            'ratio_result_qty' => 'decimal:4',
            'fabric_delivered' => 'decimal:2',
            'delivery_due_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(WorkflowEntry::class)->latest();
    }

    public function quantityFor(string $action, string $column = 'quantity'): int
    {
        return $this->entries->where('action', $action)->sum($column);
    }

    public function quantityForMaterial(string $action, string $materialType, string $column = 'quantity'): int
    {
        return $this->entries
            ->where('action', $action)
            ->filter(fn (WorkflowEntry $entry): bool => data_get($entry->metadata, 'material_type') === $materialType)
            ->sum($column);
    }

    public function availableForSewing(): int
    {
        return max(
            0,
            $this->quantityFor('cutting_report')
                - $this->quantityForMaterial('vendor_send', 'Potongan Bahan')
                - $this->quantityFor('site_transfer')
                + $this->quantityForMaterial('vendor_receive', 'Potongan Bahan')
                - $this->quantityFor('sewing'),
        );
    }

    public function availableForGarmentVendor(): int
    {
        return max(
            0,
            $this->quantityFor('sewing')
                + $this->quantityFor('site_return')
                - $this->quantityForMaterial('vendor_send', 'Baju Jahit')
                - $this->quantityFor('quality_check')
                - $this->quantityFor('quality_check', 'secondary_quantity'),
        );
    }

    public function finishedQuantity(): int
    {
        return max(
            0,
            $this->quantityFor('sewing')
                + $this->quantityFor('site_return')
                - $this->quantityForMaterial('vendor_send', 'Baju Jahit')
                + $this->quantityForMaterial('vendor_receive', 'Baju Jahit'),
        );
    }

    public function pendingVendorQuantity(?string $materialType = null): int
    {
        if ($materialType !== null) {
            return max(
                0,
                $this->quantityForMaterial('vendor_send', $materialType)
                    - $this->quantityForMaterial('vendor_receive', $materialType)
                    - $this->quantityForMaterial('vendor_receive', $materialType, 'secondary_quantity'),
            );
        }

        return max(
            0,
            $this->quantityFor('vendor_send')
                - $this->quantityFor('vendor_receive')
                - $this->quantityFor('vendor_receive', 'secondary_quantity'),
        );
    }

    public function pendingSiteQuantity(): int
    {
        return max(0, $this->quantityFor('site_transfer') - $this->quantityFor('site_receive'));
    }

    public function pendingSiteReturnQuantity(): int
    {
        return max(
            0,
            $this->quantityFor('site_receive')
                - $this->quantityFor('site_return')
                - $this->quantityFor('site_return', 'secondary_quantity'),
        );
    }

    public function pendingShipmentQuantity(): int
    {
        return max(0, $this->quantityFor('shipment') - $this->quantityFor('goods_receipt'));
    }

    public function pendingPreparedShipmentQuantity(): int
    {
        $shippedReferences = $this->entries
            ->where('action', 'shipment')
            ->pluck('reference')
            ->all();

        return (int) $this->entries
            ->where('action', 'prepare_shipment')
            ->reject(fn (WorkflowEntry $entry): bool => in_array($entry->reference, $shippedReferences, true))
            ->sum('quantity');
    }

    public function remainingQcQuantity(): int
    {
        return max(
            0,
            $this->finishedQuantity()
                - $this->quantityFor('quality_check')
                - $this->quantityFor('quality_check', 'secondary_quantity'),
        );
    }

    public function statusLabel(): string
    {
        return [
            'draft' => 'Draft',
            'fabric_requested' => 'Kain diminta',
            'fabric_in_transit' => 'Kain dikirim',
            'fabric_received' => 'Kain diterima',
            'spk_issued' => 'SPK diterbitkan',
            'cutting_complete' => 'Cutting',
            'in_production' => 'Dalam produksi',
            'quality_ready' => 'Siap QC / Kirim',
            'shipped' => 'Dalam pengiriman',
            'delivered' => 'Diterima Mydent',
            'closed' => 'Closed',
        ][$this->status] ?? 'Dalam proses';
    }
}
