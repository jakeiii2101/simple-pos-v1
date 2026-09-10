<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'sale_id',
    'authorized_by',
    'type',
    'amount',
    'reason',
    'inventory_restocked',
    'processed_at',
])]
class SaleAdjustment extends Model
{
    public const TYPE_VOID = 'void';

    public const TYPE_REFUND = 'refund';

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Sale adjustments are immutable financial history.'));
        static::deleting(fn () => throw new LogicException('Sale adjustments are immutable financial history.'));
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'inventory_restocked' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
