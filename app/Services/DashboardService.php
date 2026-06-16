<?php

namespace App\Services;

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Wallet;
use App\OrderStatus;
use App\ProductStatus;
use App\SubscriptionStatus;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(
        private DepositService $depositService,
    ) {}

    /**
     * @return array<string, int>
     */
    public function adminStats(int $tenantId): array
    {
        $today = Carbon::today()->toDateString();

        $activeOrderStatuses = [
            OrderStatus::Pending,
            OrderStatus::Assigned,
            OrderStatus::OutForDelivery,
        ];

        return [
            'active_customers' => Customer::query()
                ->where('tenant_id', $tenantId)
                ->where('status', CustomerStatus::Active)
                ->count(),
            'pending_orders' => Order::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', $activeOrderStatuses)
                ->count(),
            'today_deliveries' => Order::query()
                ->where('tenant_id', $tenantId)
                ->whereDate('scheduled_date', $today)
                ->whereIn('status', $activeOrderStatuses)
                ->count(),
            'active_subscriptions' => Subscription::query()
                ->where('tenant_id', $tenantId)
                ->where('status', SubscriptionStatus::Active)
                ->count(),
            'low_wallet_customers' => Wallet::query()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('low_balance_threshold')
                ->whereColumn('balance', '<', 'low_balance_threshold')
                ->count(),
            'active_products' => Product::query()
                ->where('tenant_id', $tenantId)
                ->where('status', ProductStatus::Active)
                ->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function adminRecentOrders(int $tenantId, int $limit = 5): array
    {
        return Order::query()
            ->where('tenant_id', $tenantId)
            ->with(['customer:id,name,code'])
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Completed])
            ->orderByDesc('scheduled_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order): array => [
                'uuid' => $order->uuid,
                'customer_name' => $order->customer?->name,
                'customer_code' => $order->customer?->code,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'scheduled_date' => $order->scheduled_date->toDateString(),
                'total' => $order->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function adminLowWalletCustomers(int $tenantId, int $limit = 5): array
    {
        return Wallet::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('low_balance_threshold')
            ->whereColumn('balance', '<', 'low_balance_threshold')
            ->with(['customer:id,name,code'])
            ->orderBy('balance')
            ->limit($limit)
            ->get()
            ->map(fn (Wallet $wallet): array => [
                'customer_id' => $wallet->customer_id,
                'customer_name' => $wallet->customer?->name,
                'customer_code' => $wallet->customer?->code,
                'balance' => $wallet->balance,
                'low_balance_threshold' => $wallet->low_balance_threshold,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function portalStats(Customer $customer): array
    {
        $customer->loadMissing(['wallet', 'deposit']);

        $wallet = $customer->wallet;
        $deposit = $customer->deposit;

        $subscription = Subscription::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
            ->latest('id')
            ->first();

        $pendingOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [
                OrderStatus::Pending,
                OrderStatus::Assigned,
                OrderStatus::OutForDelivery,
            ])
            ->count();

        $nextOrder = Order::query()
            ->where('customer_id', $customer->id)
            ->whereDate('scheduled_date', '>=', Carbon::today())
            ->whereIn('status', [
                OrderStatus::Pending,
                OrderStatus::Assigned,
                OrderStatus::OutForDelivery,
            ])
            ->orderBy('scheduled_date')
            ->first();

        $isLowBalance = $wallet !== null
            && $wallet->low_balance_threshold !== null
            && bccomp((string) $wallet->balance, (string) $wallet->low_balance_threshold, 2) < 0;

        return [
            'customer' => [
                'name' => $customer->name,
                'code' => $customer->code,
                'status' => $customer->status->value,
            ],
            'wallet' => $wallet ? [
                'balance' => $wallet->balance,
                'low_balance_threshold' => $wallet->low_balance_threshold,
                'is_low' => $isLowBalance,
            ] : null,
            'deposit' => $deposit ? [
                'balance' => $deposit->balance,
                'held_jar_count' => $this->depositService->heldJarCount($deposit),
            ] : null,
            'subscription' => $subscription ? [
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label(),
                'paused_until' => $subscription->paused_until?->toDateString(),
            ] : null,
            'pending_orders' => $pendingOrders,
            'next_delivery_date' => $nextOrder?->scheduled_date->toDateString(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function portalRecentOrders(Customer $customer, int $limit = 5): array
    {
        return Order::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('scheduled_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order): array => [
                'uuid' => $order->uuid,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'scheduled_date' => $order->scheduled_date->toDateString(),
                'total' => $order->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function agentStats(int $tenantId): array
    {
        $today = Carbon::today()->toDateString();

        $activeStatuses = [
            OrderStatus::Pending,
            OrderStatus::Assigned,
            OrderStatus::OutForDelivery,
        ];

        return [
            'today_deliveries' => Order::query()
                ->where('tenant_id', $tenantId)
                ->whereDate('scheduled_date', $today)
                ->whereIn('status', $activeStatuses)
                ->count(),
            'out_for_delivery' => Order::query()
                ->where('tenant_id', $tenantId)
                ->where('status', OrderStatus::OutForDelivery)
                ->count(),
            'delivered_today' => Order::query()
                ->where('tenant_id', $tenantId)
                ->where('status', OrderStatus::Delivered)
                ->whereDate('delivered_at', $today)
                ->count(),
            'pending_pickup' => Order::query()
                ->where('tenant_id', $tenantId)
                ->where('status', OrderStatus::Assigned)
                ->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function agentTodayDeliveries(int $tenantId, int $limit = 10): array
    {
        $today = Carbon::today()->toDateString();

        return Order::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('scheduled_date', $today)
            ->whereIn('status', [
                OrderStatus::Pending,
                OrderStatus::Assigned,
                OrderStatus::OutForDelivery,
            ])
            ->with(['customer:id,name,phone', 'address:id,address_line_1,city'])
            ->orderBy('status')
            ->orderBy('scheduled_date')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order): array => [
                'uuid' => $order->uuid,
                'customer_name' => $order->customer?->name,
                'customer_phone' => $order->customer?->phone,
                'address' => $order->address
                    ? trim("{$order->address->address_line_1}, {$order->address->city}")
                    : null,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
            ])
            ->values()
            ->all();
    }
}
