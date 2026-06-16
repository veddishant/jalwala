<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\TenantSubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantSubscription>
 */
class TenantSubscriptionFactory extends Factory
{
    protected $model = TenantSubscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $trialEndsAt = now()->addDays(14);

        return [
            'tenant_id' => Tenant::factory(),
            'plan' => 'trial',
            'status' => TenantSubscriptionStatus::Trialing,
            'trial_ends_at' => $trialEndsAt,
            'current_period_ends_at' => $trialEndsAt,
            'external_id' => null,
        ];
    }
}
