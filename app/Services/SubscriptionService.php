<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Models\SubscriptionSchedule;
use App\ProductStatus;
use App\SubscriptionStatus;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionService
{
    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     * @param  list<int>  $daysOfWeek
     */
    public function create(
        Customer $customer,
        CustomerAddress $address,
        array $items,
        array $daysOfWeek,
        string $startDate,
        ?string $notes = null,
    ): Subscription {
        if ($customer->isClosed()) {
            throw new InvalidArgumentException('Cannot create subscriptions for a closed customer.');
        }

        if ($address->customer_id !== $customer->id) {
            throw new InvalidArgumentException('Address does not belong to this customer.');
        }

        if ($items === []) {
            throw new InvalidArgumentException('Subscription must contain at least one item.');
        }

        $normalizedDays = $this->normalizeDaysOfWeek($daysOfWeek);

        return DB::transaction(function () use (
            $customer,
            $address,
            $items,
            $normalizedDays,
            $startDate,
            $notes,
        ): Subscription {
            TenantContext::setId($customer->tenant_id);

            $subscription = Subscription::query()->create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'customer_address_id' => $address->id,
                'status' => SubscriptionStatus::Active,
                'start_date' => $startDate,
                'notes' => $notes,
            ]);

            $this->syncItems($subscription, $items);
            $this->syncSchedules($subscription, $normalizedDays);

            return $subscription->load(['items.product', 'schedules', 'customer', 'address']);
        });
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     * @param  list<int>  $daysOfWeek
     */
    public function update(
        Subscription $subscription,
        CustomerAddress $address,
        array $items,
        array $daysOfWeek,
        ?string $notes = null,
    ): Subscription {
        if (! $subscription->status->isManageable()) {
            throw new InvalidArgumentException('This subscription cannot be updated.');
        }

        if ($address->customer_id !== $subscription->customer_id) {
            throw new InvalidArgumentException('Address does not belong to this customer.');
        }

        $normalizedDays = $this->normalizeDaysOfWeek($daysOfWeek);

        return DB::transaction(function () use (
            $subscription,
            $address,
            $items,
            $normalizedDays,
            $notes,
        ): Subscription {
            $subscription->update([
                'customer_address_id' => $address->id,
                'notes' => $notes,
            ]);

            $this->syncItems($subscription, $items);
            $this->syncSchedules($subscription, $normalizedDays);

            return $subscription->fresh(['items.product', 'schedules', 'customer', 'address', 'pauses']);
        });
    }

    public function cancel(Subscription $subscription): Subscription
    {
        if ($subscription->status === SubscriptionStatus::Cancelled) {
            throw new InvalidArgumentException('Subscription is already cancelled.');
        }

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'paused_until' => null,
        ]);

        return $subscription->fresh(['items.product', 'schedules', 'customer', 'address', 'pauses']);
    }

    /**
     * @return list<string>
     */
    public function upcomingDeliveryDates(Subscription $subscription, int $daysAhead = 14): array
    {
        if ($subscription->status === SubscriptionStatus::Cancelled) {
            return [];
        }

        $subscription->loadMissing(['schedules', 'pauses']);

        $scheduledDays = $subscription->schedules
            ->pluck('day_of_week')
            ->map(fn ($day): int => (int) $day)
            ->all();

        if ($scheduledDays === []) {
            return [];
        }

        $dates = [];
        $cursor = today();
        $end = today()->addDays($daysAhead);

        while ($cursor->lte($end)) {
            if (
                $cursor->toDateString() >= $subscription->start_date->toDateString()
                && in_array($cursor->dayOfWeek, $scheduledDays, true)
                && ! $subscription->isPausedOnDate($cursor)
            ) {
                $dates[] = $cursor->toDateString();
            }

            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    private function syncItems(Subscription $subscription, array $items): void
    {
        $subscription->items()->delete();

        foreach ($items as $item) {
            $product = Product::query()
                ->where('tenant_id', $subscription->tenant_id)
                ->where('id', $item['product_id'])
                ->where('status', ProductStatus::Active)
                ->first();

            if ($product === null) {
                throw new InvalidArgumentException('One or more products are invalid or inactive.');
            }

            $quantity = (int) $item['quantity'];

            if ($quantity < 1) {
                throw new InvalidArgumentException('Item quantity must be at least 1.');
            }

            SubscriptionItem::query()->create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => number_format((float) $product->unit_price, 2, '.', ''),
            ]);
        }
    }

    /**
     * @param  list<int>  $daysOfWeek
     */
    private function syncSchedules(Subscription $subscription, array $daysOfWeek): void
    {
        $subscription->schedules()->delete();

        foreach ($daysOfWeek as $day) {
            SubscriptionSchedule::query()->create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'day_of_week' => $day,
            ]);
        }
    }

    /**
     * @param  list<int>  $daysOfWeek
     * @return list<int>
     */
    private function normalizeDaysOfWeek(array $daysOfWeek): array
    {
        $normalized = collect($daysOfWeek)
            ->map(fn ($day): int => (int) $day)
            ->filter(fn (int $day): bool => $day >= 0 && $day <= 6)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($normalized === []) {
            throw new InvalidArgumentException('At least one delivery day is required.');
        }

        return $normalized;
    }
}
