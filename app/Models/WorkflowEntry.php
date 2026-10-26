<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowEntry extends Model
{
    protected $fillable = [
        'production_order_id',
        'user_id',
        'action',
        'reference',
        'quantity',
        'secondary_quantity',
        'metadata',
        'notes',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::created(function (WorkflowEntry $entry): void {
            AuditLog::query()->create([
                'user_id' => $entry->user_id,
                'event' => $entry->action,
                'subject_type' => 'production_order',
                'subject_id' => (string) $entry->production_order_id,
                'description' => str($entry->action)->replace('_', ' ')->title().' pada Purchase Order.',
                'metadata' => [
                    'reference' => $entry->reference,
                    'quantity' => $entry->quantity,
                    'secondary_quantity' => $entry->secondary_quantity,
                    'details' => $entry->metadata,
                    'notes' => $entry->notes,
                ],
            ]);
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
