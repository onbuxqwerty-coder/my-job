<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlanFeature;
use App\Enums\PlanType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'type',
        'name',
        'description',
        'price_monthly',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type'       => PlanType::class,
            'features'   => 'array',
            'is_active'  => 'boolean',
        ];
    }

    public function feature(PlanFeature $feature): mixed
    {
        return $this->features[$feature->value] ?? null;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(EmployerSubscription::class, 'plan_id');
    }
}
