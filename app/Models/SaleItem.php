<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'product_id',
    'product_name',
    'sku',
    'unit_price',
    'quantity',
    'line_total',
    'tax_type',
    'is_senior_pwd_discount_eligible',
    'discount_amount',
    'vat_amount',
    'net_total',
    'vat_exemption_amount',
])]
class SaleItem extends Model
{
    use HasFactory;

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
            'is_senior_pwd_discount_eligible' => 'boolean',
            'discount_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'net_total' => 'decimal:2',
            'vat_exemption_amount' => 'decimal:2',
        ];
    }
}
