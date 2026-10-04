<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'status',
    'owner_user_id',
    'approved_by',
    'approved_at',
    'rejected_at',
    'rejection_reason',
])]
class Account extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    public function owner(): BelongsTo
    {
        $relation = $this->belongsTo(User::class, 'owner_user_id');
        $relation->getQuery()->withoutGlobalScope('account');

        return $relation;
    }

    public function approver(): BelongsTo
    {
        $relation = $this->belongsTo(User::class, 'approved_by');
        $relation->getQuery()->withoutGlobalScope('account');

        return $relation;
    }

    public function users(): HasMany
    {
        $relation = $this->hasMany(User::class);
        $relation->getQuery()->withoutGlobalScope('account');

        return $relation;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
