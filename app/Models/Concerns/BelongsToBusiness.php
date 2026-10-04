<?php

namespace App\Models\Concerns;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $builder): void {
            $businessId = auth()->user()?->business_id;

            if ($businessId !== null) {
                $builder->where(
                    $builder->getModel()->qualifyColumn('business_id'),
                    $businessId,
                );
            }
        });

        static::creating(function ($model): void {
            if ($model->business_id !== null) {
                return;
            }

            $businessId = auth()->user()?->business_id;

            if ($businessId !== null) {
                $model->business_id = $businessId;
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
