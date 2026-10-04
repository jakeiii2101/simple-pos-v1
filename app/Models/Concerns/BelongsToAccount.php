<?php

namespace App\Models\Concerns;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToAccount
{
    public static function bootBelongsToAccount(): void
    {
        static::addGlobalScope('account', function (Builder $builder): void {
            $accountId = Auth::user()?->account_id;

            if ($accountId !== null) {
                $builder->where($builder->qualifyColumn('account_id'), $accountId);
            }
        });

        static::creating(function ($model): void {
            if ($model->account_id === null && Auth::user()?->account_id !== null) {
                $model->account_id = Auth::user()->account_id;
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
