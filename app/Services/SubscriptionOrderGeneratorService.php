<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\Tenant;
use App\SubscriptionStatus;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Support\Carbon;

class SubscriptionOrderGeneratorService
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function generateForDate(string $targetDate): int
    {
        $generated = 0;

        $tenants = Tenant::query()
            ->where('status', TenantStatus::Active)
            ->get();

        foreach ($tenants as $tenant) {
            $generated += $this->generateForTenant($tenant, $targetDate);
        }

        return $generated;
    }

    public function generateForTenant(Tenant $tenant, string $targetDate): int
    {
        TenantContext::setId($tenant->id);

        $target = Carbon::parse($targetDate);
        $dayOfWeek = $target->dayOfWeek;
        $generated = 0;

        $subscriptions = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', SubscriptionStatus::Active)
            ->where('start_date', '<=', $targetDate)
            ->with(['items', 'schedules', 'pauses'])
            ->get();

        foreach ($subscriptions as $subscription) {
            if (! $subscription->hasScheduleOnDay($dayOfWeek)) {
                continue;
            }

            if ($subscription->isPausedOnDate($target)) {
                continue;
            }

            if ($subscription->end_date !== null && $target->toDateString() > $subscription->end_date->toDateString()) {
                continue;
            }

            $exists = Order::query()
                ->where('subscription_id', $subscription->id)
                ->whereDate('scheduled_date', $targetDate)
                ->exists();

            if ($exists) {
                continue;
            }

            $order = $this->orderService->createFromSubscription(
                subscription: $subscription,
                scheduledDate: $targetDate,
            );

            $this->orderService->confirm($order);

            $generated++;
        }

        return $generated;
    }
}
