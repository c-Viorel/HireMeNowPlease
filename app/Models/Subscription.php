<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
