<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPause;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPause>
 */
class SubscriptionPauseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(3);

        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(7)->toDateString(),
            'reason' => 'Vacation',
            'created_by' => null,
        ];
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
        ]);
    }
}
