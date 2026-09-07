<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'method',
    'amount',
    'amount_tendered',
    'change_due',
    'reference',
])]
class Payment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';
    public const METHOD_GCASH = 'gcash';
    public const METHOD_CARD = 'card';
    public const METHOD_OTHER = 'other';

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_tendered' => 'decimal:2',
            'change_due' => 'decimal:2',
        ];
    }
}
