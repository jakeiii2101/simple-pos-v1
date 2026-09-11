<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['refund_number', 'sale_id', 'authorized_by', 'gross_amount', 'discount_amount', 'vat_exemption_amount', 'vatable_sales', 'vat_amount', 'vat_exempt_sales', 'zero_rated_sales', 'non_vat_sales', 'refund_amount', 'reason', 'inventory_restocked', 'processed_at'])]
class SaleRefund extends Model
{
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleRefundItem::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Partial refunds are immutable financial history.'));
        static::deleting(fn () => throw new LogicException('Partial refunds are immutable financial history.'));
    }

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'vat_exemption_amount' => 'decimal:2',
            'vatable_sales' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'vat_exempt_sales' => 'decimal:2',
            'zero_rated_sales' => 'decimal:2',
            'non_vat_sales' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'inventory_restocked' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
