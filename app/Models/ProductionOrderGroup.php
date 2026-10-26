<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrderGroup extends Model
{
    protected $fillable = [
        'po_number',
        'po_date',
        'created_by',
        'spk_signature_path',
        'spk_signed_by',
        'spk_signed_at',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'spk_signed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spk_signed_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'po_number', 'po_number')->orderBy('id');
    }
}
