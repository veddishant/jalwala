<?php

namespace App\Repositories;

use App\DepositTransactionType;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\DepositTransaction;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\OrderStatus;
use App\WalletTransactionCategory;
use App\WalletTransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportRepository
{
    /**
     * @param  array{
     *     date_from: string,
     *     date_to: string,
     *     grain: string,
     *     customer_id: int|null,
     *     product_id: int|null,
     *     source: string|null,
     *     agent_id: int|null
     * }  $filters
     * @return array{
     *     summary: array<string, mixed>,
     *     by_period: list<array<string, mixed>>,
     *     by_product: list<array<string, mixed>>,
     *     rows: list<array<string, mixed>>
     * }
     */
    public function sales(int $tenantId, array $filters): array
    {
        $query = $this->completedOrdersQuery($tenantId, $filters);

        if ($filters['source'] !== null) {
            $query->where('source', $filters['source']);
        }

        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->selectRaw('COUNT(*) as order_count')
            ->first();

        $orderCount = (int) ($totals->order_count ?? 0);
        $totalRevenue = (string) ($totals->total_revenue ?? '0.00');
        $avgOrderValue = $orderCount > 0
            ? bcdiv($totalRevenue, (string) $orderCount, 2)
            : '0.00';

        $periodExpression = $this->periodExpression('orders.scheduled_date', $filters['grain']);

        $byPeriod = (clone $query)
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COUNT(*) as order_count')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row): array => [
                'period' => (string) $row->period,
                'revenue' => number_format((float) $row->revenue, 2, '.', ''),
                'order_count' => (int) $row->order_count,
            ])
            ->values()
            ->all();

        $byProduct = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.tenant_id', $tenantId)
            ->whereIn('orders.status', [OrderStatus::Delivered->value, OrderStatus::Completed->value])
            ->whereBetween('orders.scheduled_date', [$filters['date_from'], $filters['date_to']])
            ->when($filters['customer_id'], fn ($q, int $id) => $q->where('orders.customer_id', $id))
            ->when($filters['product_id'], fn ($q, int $id) => $q->where('order_items.product_id', $id))
            ->when($filters['source'], fn ($q, string $source) => $q->where('orders.source', $source))
            ->selectRaw('products.name as product_name')
            ->selectRaw('COALESCE(SUM(order_items.line_total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity')
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row): array => [
                'product_name' => $row->product_name,
                'revenue' => number_format((float) $row->revenue, 2, '.', ''),
                'quantity' => (int) $row->quantity,
                'order_count' => (int) $row->order_count,
            ])
            ->values()
            ->all();

        $rows = (clone $query)
            ->with(['customer:id,name,code'])
            ->orderByDesc('scheduled_date')
            ->limit(100)
            ->get()
            ->map(fn (Order $order): array => [
                'uuid' => $order->uuid,
                'customer_name' => $order->customer?->name,
                'customer_code' => $order->customer?->code,
                'source' => $order->source->value,
                'source_label' => $order->source->label(),
                'scheduled_date' => $order->scheduled_date->toDateString(),
                'total' => $order->total,
                'status' => $order->status->value,
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'total_revenue' => number_format((float) $totalRevenue, 2, '.', ''),
                'order_count' => $orderCount,
                'avg_order_value' => $avgOrderValue,
            ],
            'by_period' => $byPeriod,
            'by_product' => $byProduct,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function consumption(int $tenantId, array $filters): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.tenant_id', $tenantId)
            ->whereIn('orders.status', [OrderStatus::Delivered->value, OrderStatus::Completed->value])
            ->whereBetween('orders.scheduled_date', [$filters['date_from'], $filters['date_to']])
            ->when($filters['customer_id'], fn ($q, int $id) => $q->where('orders.customer_id', $id))
            ->when($filters['product_id'], fn ($q, int $id) => $q->where('order_items.product_id', $id))
            ->selectRaw('customers.name as customer_name')
            ->selectRaw('customers.code as customer_code')
            ->selectRaw('products.name as product_name')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as units')
            ->selectRaw('COALESCE(SUM(order_items.quantity * COALESCE(products.capacity_liters, 0)), 0) as liters')
            ->groupBy('customers.id', 'customers.name', 'customers.code', 'products.id', 'products.name')
            ->orderByDesc('units')
            ->get();

        $totalUnits = (int) $rows->sum('units');
        $totalLiters = (float) $rows->sum('liters');

        $byCustomer = $rows
            ->groupBy('customer_name')
            ->map(fn (Collection $group, string $name): array => [
                'customer_name' => $name,
                'customer_code' => $group->first()->customer_code,
                'units' => (int) $group->sum('units'),
                'liters' => number_format((float) $group->sum('liters'), 2, '.', ''),
            ])
            ->values()
            ->sortByDesc('units')
            ->values()
            ->all();

        $byProduct = $rows
            ->groupBy('product_name')
            ->map(fn (Collection $group, string $name): array => [
                'product_name' => $name,
                'units' => (int) $group->sum('units'),
                'liters' => number_format((float) $group->sum('liters'), 2, '.', ''),
            ])
            ->values()
            ->sortByDesc('units')
            ->values()
            ->all();

        return [
            'summary' => [
                'total_units' => $totalUnits,
                'total_liters' => number_format($totalLiters, 2, '.', ''),
            ],
            'by_customer' => $byCustomer,
            'by_product' => $byProduct,
            'rows' => $rows->map(fn ($row): array => [
                'customer_name' => $row->customer_name,
                'customer_code' => $row->customer_code,
                'product_name' => $row->product_name,
                'units' => (int) $row->units,
                'liters' => number_format((float) $row->liters, 2, '.', ''),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function wallet(int $tenantId, array $filters): array
    {
        $transactionsQuery = WalletTransaction::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->when($filters['customer_id'], function (Builder $query, int $customerId): void {
                $query->whereHas('wallet', fn (Builder $walletQuery) => $walletQuery->where('customer_id', $customerId));
            });

        $topUps = (clone $transactionsQuery)
            ->where('category', WalletTransactionCategory::TopUp)
            ->sum('amount');

        $debits = (clone $transactionsQuery)
            ->where('type', WalletTransactionType::Debit)
            ->sum('amount');

        $adjustments = (clone $transactionsQuery)
            ->where('category', WalletTransactionCategory::Adjustment)
            ->sum('amount');

        $negativeCount = Wallet::query()
            ->where('tenant_id', $tenantId)
            ->where('balance', '<', 0)
            ->when($filters['customer_id'], fn (Builder $q, int $id) => $q->where('customer_id', $id))
            ->count();

        $rows = (clone $transactionsQuery)
            ->with(['wallet.customer:id,name,code', 'createdBy:id,name'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (WalletTransaction $transaction): array => [
                'customer_name' => $transaction->wallet?->customer?->name,
                'customer_code' => $transaction->wallet?->customer?->code,
                'type' => $transaction->type->value,
                'category' => $transaction->category->value,
                'amount' => $transaction->amount,
                'balance_after' => $transaction->balance_after,
                'description' => $transaction->description,
                'created_by' => $transaction->createdBy?->name,
                'created_at' => $transaction->created_at?->toISOString(),
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'total_top_ups' => number_format((float) $topUps, 2, '.', ''),
                'total_debits' => number_format((float) $debits, 2, '.', ''),
                'total_adjustments' => number_format((float) $adjustments, 2, '.', ''),
                'negative_balance_count' => $negativeCount,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function deposits(int $tenantId, array $filters): array
    {
        $transactionsQuery = DepositTransaction::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->when($filters['product_id'], fn (Builder $q, int $id) => $q->where('product_id', $id))
            ->when($filters['customer_id'], function (Builder $query, int $customerId): void {
                $query->whereHas('customerDeposit', fn (Builder $depositQuery) => $depositQuery->where('customer_id', $customerId));
            });

        $collected = (clone $transactionsQuery)
            ->where('type', DepositTransactionType::Collect)
            ->sum('amount');

        $refunded = (clone $transactionsQuery)
            ->where('type', DepositTransactionType::Refund)
            ->sum('amount');

        $totalHeld = CustomerDeposit::query()
            ->where('tenant_id', $tenantId)
            ->when($filters['customer_id'], fn (Builder $q, int $id) => $q->where('customer_id', $id))
            ->sum('balance');

        $byProduct = (clone $transactionsQuery)
            ->with('product:id,name')
            ->whereNotNull('product_id')
            ->get()
            ->groupBy('product_id')
            ->map(function (Collection $group): array {
                $product = $group->first()->product;

                return [
                    'product_name' => $product?->name ?? 'Unknown',
                    'collected' => number_format((float) $group->where('type', DepositTransactionType::Collect)->sum('amount'), 2, '.', ''),
                    'refunded' => number_format((float) $group->where('type', DepositTransactionType::Refund)->sum('amount'), 2, '.', ''),
                    'jar_count' => (int) $group->sum('jar_count'),
                ];
            })
            ->values()
            ->sortByDesc('collected')
            ->values()
            ->all();

        $rows = CustomerDeposit::query()
            ->where('tenant_id', $tenantId)
            ->with('customer:id,name,code')
            ->when($filters['customer_id'], fn (Builder $q, int $id) => $q->where('customer_id', $id))
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->limit(100)
            ->get()
            ->map(fn (CustomerDeposit $deposit): array => [
                'customer_name' => $deposit->customer?->name,
                'customer_code' => $deposit->customer?->code,
                'balance' => $deposit->balance,
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'total_held' => number_format((float) $totalHeld, 2, '.', ''),
                'total_collected' => number_format((float) $collected, 2, '.', ''),
                'total_refunded' => number_format((float) $refunded, 2, '.', ''),
            ],
            'by_product' => $byProduct,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outstanding(int $tenantId): array
    {
        $wallets = Wallet::query()
            ->where('tenant_id', $tenantId)
            ->where('balance', '<', 0)
            ->with('customer:id,name,code,phone,status')
            ->orderBy('balance')
            ->get();

        $rows = $wallets->map(function (Wallet $wallet): array {
            $firstNegativeAt = WalletTransaction::query()
                ->where('wallet_id', $wallet->id)
                ->where('balance_after', '<', 0)
                ->orderBy('created_at')
                ->value('created_at');

            $daysNegative = $firstNegativeAt !== null
                ? (int) now()->diffInDays($firstNegativeAt)
                : 0;

            return [
                'customer_name' => $wallet->customer?->name,
                'customer_code' => $wallet->customer?->code,
                'phone' => $wallet->customer?->phone,
                'status' => $wallet->customer?->status->value,
                'amount_owed' => ltrim($wallet->balance, '-'),
                'balance' => $wallet->balance,
                'days_negative' => $daysNegative,
                'aging_bucket' => $this->agingBucket($daysNegative),
            ];
        })->values()->all();

        $byAging = collect($rows)
            ->groupBy('aging_bucket')
            ->map(fn (Collection $group, string $bucket): array => [
                'bucket' => $bucket,
                'customer_count' => $group->count(),
                'total_owed' => number_format(
                    $group->sum(fn (array $row): float => (float) $row['amount_owed']),
                    2,
                    '.',
                    '',
                ),
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'customer_count' => count($rows),
                'total_owed' => number_format(
                    collect($rows)->sum(fn (array $row): float => (float) $row['amount_owed']),
                    2,
                    '.',
                    '',
                ),
            ],
            'by_aging' => $byAging,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function agentPerformance(int $tenantId, array $filters): array
    {
        $agentIds = User::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'delivery-agent'))
            ->when($filters['agent_id'], fn (Builder $q, int $id) => $q->where('id', $id))
            ->pluck('id');

        $histories = OrderStatusHistory::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('changed_by', $agentIds)
            ->whereIn('to_status', [OrderStatus::Delivered, OrderStatus::Failed])
            ->whereBetween('created_at', [
                $filters['date_from'].' 00:00:00',
                $filters['date_to'].' 23:59:59',
            ])
            ->with(['changedBy:id,name', 'order:id,uuid,scheduled_date,delivered_at'])
            ->get();

        $rows = $histories
            ->groupBy('changed_by')
            ->map(function (Collection $group): array {
                $agent = $group->first()->changedBy;
                $delivered = $group->where('to_status', OrderStatus::Delivered);
                $failed = $group->where('to_status', OrderStatus::Failed);

                $deliveryTimes = $delivered
                    ->filter(fn (OrderStatusHistory $history): bool => $history->order?->delivered_at !== null)
                    ->map(function (OrderStatusHistory $history): int {
                        $assignedAt = OrderStatusHistory::query()
                            ->where('order_id', $history->order_id)
                            ->whereIn('to_status', [OrderStatus::Assigned, OrderStatus::OutForDelivery])
                            ->orderBy('created_at')
                            ->value('created_at');

                        if ($assignedAt === null || $history->order?->delivered_at === null) {
                            return 0;
                        }

                        return (int) $assignedAt->diffInMinutes($history->order->delivered_at);
                    })
                    ->filter(fn (int $minutes): bool => $minutes > 0);

                $avgMinutes = $deliveryTimes->isNotEmpty()
                    ? (int) round($deliveryTimes->avg())
                    : null;

                $daysActive = $delivered
                    ->map(fn (OrderStatusHistory $h) => $h->created_at?->toDateString())
                    ->unique()
                    ->count();

                return [
                    'agent_name' => $agent?->name ?? 'Unknown',
                    'delivered_count' => $delivered->count(),
                    'failed_count' => $failed->count(),
                    'avg_delivery_minutes' => $avgMinutes,
                    'orders_per_day' => $daysActive > 0
                        ? number_format($delivered->count() / $daysActive, 1, '.', '')
                        : '0.0',
                ];
            })
            ->values()
            ->sortByDesc('delivered_count')
            ->values()
            ->all();

        return [
            'summary' => [
                'total_delivered' => collect($rows)->sum('delivered_count'),
                'total_failed' => collect($rows)->sum('failed_count'),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    public function customersForTenant(int $tenantId): array
    {
        return Customer::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, sku: string}>
     */
    public function productsForTenant(int $tenantId): array
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function agentsForTenant(int $tenantId): array
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'delivery-agent'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Order>
     */
    private function completedOrdersQuery(int $tenantId, array $filters): Builder
    {
        return Order::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [OrderStatus::Delivered, OrderStatus::Completed])
            ->whereBetween('scheduled_date', [$filters['date_from'], $filters['date_to']])
            ->when($filters['customer_id'], fn (Builder $q, int $id) => $q->where('customer_id', $id))
            ->when($filters['product_id'], function (Builder $query, int $productId): void {
                $query->whereHas('items', fn (Builder $items) => $items->where('product_id', $productId));
            });
    }

    private function periodExpression(string $column, string $grain): string
    {
        return match ($grain) {
            'weekly' => "TO_CHAR(DATE_TRUNC('week', {$column}), 'YYYY-MM-DD')",
            'monthly' => "TO_CHAR(DATE_TRUNC('month', {$column}), 'YYYY-MM')",
            default => "TO_CHAR({$column}, 'YYYY-MM-DD')",
        };
    }

    private function agingBucket(int $days): string
    {
        return match (true) {
            $days <= 30 => '0-30 days',
            $days <= 60 => '31-60 days',
            $days <= 90 => '61-90 days',
            default => '90+ days',
        };
    }
}
