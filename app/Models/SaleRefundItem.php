<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['sale_refund_id', 'sale_item_id', 'quantity', 'gross_amount', 'discount_amount', 'vat_exemption_amount', 'vat_amount', 'refund_amount'])]
class SaleRefundItem extends Model
{
    public function refund(): BelongsTo
    {
        return $this->belongsTo(SaleRefund::class, 'sale_refund_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Partial refund items are immutable financial history.'));
        static::deleting(fn () => throw new LogicException('Partial refund items are immutable financial history.'));
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'gross_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'vat_exemption_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }
}
