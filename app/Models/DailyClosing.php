<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['business_date', 'reading_number', 'closed_by', 'closed_at', 'snapshot', 'notes'])]
class DailyClosing extends Model
{
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Z-reading closures are immutable.'));
        static::deleting(fn () => throw new LogicException('Z-reading closures are immutable.'));
    }

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'closed_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }
}
