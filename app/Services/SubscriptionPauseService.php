<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPause;
use App\SubscriptionStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionPauseService
{
    public function pause(
        Subscription $subscription,
        string $startDate,
        string $endDate,
        ?int $createdBy = null,
        ?string $reason = null,
    ): Subscription {
        if (! $subscription->status->isManageable()) {
            throw new InvalidArgumentException('This subscription cannot be paused.');
        }

        if ($endDate < $startDate) {
            throw new InvalidArgumentException('Pause end date must be on or after the start date.');
        }

        return DB::transaction(function () use (
            $subscription,
            $startDate,
            $endDate,
            $createdBy,
            $reason,
        ): Subscription {
            SubscriptionPause::query()->create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
                'created_by' => $createdBy,
            ]);

            $today = today()->toDateString();
            $isCurrentlyPaused = $startDate <= $today && $endDate >= $today;

            $subscription->update([
                'status' => $isCurrentlyPaused
                    ? SubscriptionStatus::Paused
                    : $subscription->status,
                'paused_until' => $isCurrentlyPaused ? $endDate : $subscription->paused_until,
            ]);

            return $subscription->fresh(['items.product', 'schedules', 'pauses', 'customer', 'address']);
        });
    }

    public function resume(Subscription $subscription): Subscription
    {
        if ($subscription->status === SubscriptionStatus::Cancelled) {
            throw new InvalidArgumentException('Cancelled subscriptions cannot be resumed.');
        }

        return DB::transaction(function () use ($subscription): Subscription {
            $today = today()->toDateString();

            SubscriptionPause::query()
                ->where('subscription_id', $subscription->id)
                ->where('end_date', '>=', $today)
                ->delete();

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'paused_until' => null,
            ]);

            return $subscription->fresh(['items.product', 'schedules', 'pauses', 'customer', 'address']);
        });
    }
}
