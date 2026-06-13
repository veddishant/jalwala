<?php

namespace App\Services;

use App\Events\OrderDelivered;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Tenant;
use App\OrderSource;
use App\OrderStatus;
use App\ProductStatus;
use App\Support\TenantContext;
use App\WalletTransactionCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private WalletService $walletService,
    ) {}

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function create(
        Customer $customer,
        CustomerAddress $address,
        array $items,
        string $scheduledDate,
        OrderSource $source,
        ?int $createdBy = null,
        ?string $notes = null,
    ): Order {
        if ($customer->isClosed()) {
            throw new InvalidArgumentException('Cannot create orders for a closed customer.');
        }

        if ($address->customer_id !== $customer->id) {
            throw new InvalidArgumentException('Address does not belong to this customer.');
        }

        if ($items === []) {
            throw new InvalidArgumentException('Order must contain at least one item.');
        }

        return DB::transaction(function () use (
            $customer,
            $address,
            $items,
            $scheduledDate,
            $source,
            $createdBy,
            $notes,
        ): Order {
            TenantContext::setId($customer->tenant_id);

            $lineItems = $this->buildLineItems($customer->tenant_id, $items);
            $subtotal = $this->sumLineTotals($lineItems);

            $order = Order::query()->create([
                'tenant_id' => $customer->tenant_id,
                'uuid' => (string) Str::uuid(),
                'customer_id' => $customer->id,
                'customer_address_id' => $address->id,
                'source' => $source,
                'status' => OrderStatus::Draft,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'wallet_amount_charged' => '0.00',
                'scheduled_date' => $scheduledDate,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            foreach ($lineItems as $lineItem) {
                OrderItem::query()->create([
                    'tenant_id' => $customer->tenant_id,
                    'order_id' => $order->id,
                    ...$lineItem,
                ]);
            }

            $this->recordStatusChange(
                order: $order,
                fromStatus: null,
                toStatus: OrderStatus::Draft,
                changedBy: $createdBy,
                notes: 'Order created',
            );

            return $order->load(['items.product', 'customer', 'address', 'statusHistories.changedBy']);
        });
    }

    public function createFromSubscription(
        Subscription $subscription,
        string $scheduledDate,
        ?int $createdBy = null,
    ): Order {
        $subscription->loadMissing(['customer', 'address', 'items']);

        if (! $subscription->isActive()) {
            throw new InvalidArgumentException('Subscription is not active.');
        }

        if ($subscription->items->isEmpty()) {
            throw new InvalidArgumentException('Subscription has no items.');
        }

        return DB::transaction(function () use ($subscription, $scheduledDate, $createdBy): Order {
            TenantContext::setId($subscription->tenant_id);

            $lineItems = $subscription->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                'line_total' => bcmul(
                    number_format((float) $item->unit_price, 2, '.', ''),
                    (string) $item->quantity,
                    2,
                ),
            ])->all();

            $subtotal = $this->sumLineTotals($lineItems);

            $order = Order::query()->create([
                'tenant_id' => $subscription->tenant_id,
                'uuid' => (string) Str::uuid(),
                'customer_id' => $subscription->customer_id,
                'customer_address_id' => $subscription->customer_address_id,
                'subscription_id' => $subscription->id,
                'source' => OrderSource::Subscription,
                'status' => OrderStatus::Draft,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'wallet_amount_charged' => '0.00',
                'scheduled_date' => $scheduledDate,
                'notes' => 'Auto-generated from subscription',
                'created_by' => $createdBy,
            ]);

            foreach ($lineItems as $lineItem) {
                OrderItem::query()->create([
                    'tenant_id' => $subscription->tenant_id,
                    'order_id' => $order->id,
                    ...$lineItem,
                ]);
            }

            $this->recordStatusChange(
                order: $order,
                fromStatus: null,
                toStatus: OrderStatus::Draft,
                changedBy: $createdBy,
                notes: 'Order generated from subscription',
            );

            return $order->load(['items.product', 'customer', 'address', 'statusHistories.changedBy']);
        });
    }

    public function confirm(Order $order, ?int $confirmedBy = null): Order
    {
        return $this->transition(
            order: $order,
            toStatus: OrderStatus::Pending,
            changedBy: $confirmedBy,
            notes: 'Order confirmed',
        );
    }

    public function cancel(Order $order, int $cancelledBy, ?string $reason = null): Order
    {
        if (! $order->status->isCancellable()) {
            throw new InvalidArgumentException('This order cannot be cancelled.');
        }

        return DB::transaction(function () use ($order, $cancelledBy, $reason): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $lockedOrder->status->isCancellable()) {
                throw new InvalidArgumentException('This order cannot be cancelled.');
            }

            $fromStatus = $lockedOrder->status;

            $this->refundWalletIfCharged($lockedOrder, $cancelledBy, $reason);

            $lockedOrder->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->recordStatusChange(
                order: $lockedOrder,
                fromStatus: $fromStatus,
                toStatus: OrderStatus::Cancelled,
                changedBy: $cancelledBy,
                notes: $reason,
            );

            return $lockedOrder->fresh(['items.product', 'customer', 'address', 'statusHistories.changedBy']);
        });
    }

    public function transition(
        Order $order,
        OrderStatus $toStatus,
        ?int $changedBy = null,
        ?string $notes = null,
        array $emptiesCollected = [],
    ): Order {
        return DB::transaction(function () use ($order, $toStatus, $changedBy, $notes, $emptiesCollected): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $fromStatus = $lockedOrder->status;

            if (! $this->canTransition($fromStatus, $toStatus)) {
                throw new InvalidArgumentException(
                    "Cannot transition order from {$fromStatus->value} to {$toStatus->value}.",
                );
            }

            $tenant = Tenant::query()->findOrFail($lockedOrder->tenant_id);
            $policy = $this->walletDebitPolicy($tenant);

            if ($toStatus === OrderStatus::Pending && $policy === 'on_confirm') {
                $this->chargeWallet($lockedOrder, $changedBy);
            }

            if ($toStatus === OrderStatus::Delivered) {
                $lockedOrder->delivered_at = now();

                if ($policy === 'on_delivery' && ! $lockedOrder->isWalletCharged()) {
                    $this->chargeWallet($lockedOrder, $changedBy);
                }
            }

            $lockedOrder->status = $toStatus;
            $lockedOrder->save();

            $this->recordStatusChange(
                order: $lockedOrder,
                fromStatus: $fromStatus,
                toStatus: $toStatus,
                changedBy: $changedBy,
                notes: $notes,
            );

            $freshOrder = $lockedOrder->fresh(['items.product', 'customer', 'address', 'statusHistories.changedBy']);

            if ($toStatus === OrderStatus::Delivered && $freshOrder !== null) {
                OrderDelivered::dispatch($freshOrder, $emptiesCollected, $changedBy);
            }

            return $freshOrder ?? $lockedOrder;
        });
    }

    /**
     * @return list<OrderStatus>
     */
    public function allowedTransitions(OrderStatus $fromStatus): array
    {
        return match ($fromStatus) {
            OrderStatus::Draft => [OrderStatus::Pending, OrderStatus::Cancelled],
            OrderStatus::Pending => [OrderStatus::Assigned, OrderStatus::Cancelled],
            OrderStatus::Assigned => [OrderStatus::OutForDelivery, OrderStatus::Cancelled],
            OrderStatus::OutForDelivery => [OrderStatus::Delivered, OrderStatus::Failed],
            OrderStatus::Failed => [OrderStatus::Assigned, OrderStatus::Cancelled],
            OrderStatus::Delivered => [OrderStatus::Completed],
            default => [],
        };
    }

    public function canTransition(OrderStatus $fromStatus, OrderStatus $toStatus): bool
    {
        return in_array($toStatus, $this->allowedTransitions($fromStatus), true);
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     * @return list<array{product_id: int, quantity: int, unit_price: string, line_total: string}>
     */
    private function buildLineItems(int $tenantId, array $items): array
    {
        $lineItems = [];

        foreach ($items as $item) {
            $product = Product::query()
                ->where('tenant_id', $tenantId)
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

            $unitPrice = number_format((float) $product->unit_price, 2, '.', '');
            $lineTotal = bcmul($unitPrice, (string) $quantity, 2);

            $lineItems[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        return $lineItems;
    }

    /**
     * @param  list<array{line_total: string}>  $lineItems
     */
    private function sumLineTotals(array $lineItems): string
    {
        return array_reduce(
            $lineItems,
            fn (string $carry, array $item): string => bcadd($carry, $item['line_total'], 2),
            '0.00',
        );
    }

    private function chargeWallet(Order $order, ?int $chargedBy): void
    {
        if ($order->isWalletCharged()) {
            return;
        }

        $order->loadMissing('customer');
        $wallet = $this->walletService->ensureForCustomer($order->customer);

        $this->walletService->debit(
            wallet: $wallet,
            amount: $order->total,
            category: WalletTransactionCategory::OrderPayment,
            idempotencyKey: "order-payment-{$order->id}",
            description: "Payment for order {$order->uuid}",
            createdBy: $chargedBy,
            referenceType: 'order',
            referenceId: $order->id,
        );

        $order->update(['wallet_amount_charged' => $order->total]);
    }

    private function refundWalletIfCharged(Order $order, int $refundedBy, ?string $reason): void
    {
        if (! $order->isWalletCharged()) {
            return;
        }

        $order->loadMissing('customer');
        $wallet = $this->walletService->ensureForCustomer($order->customer);

        $this->walletService->credit(
            wallet: $wallet,
            amount: $order->wallet_amount_charged,
            category: WalletTransactionCategory::Refund,
            idempotencyKey: "order-refund-{$order->id}",
            description: $reason ?? "Refund for cancelled order {$order->uuid}",
            createdBy: $refundedBy,
            referenceType: 'order',
            referenceId: $order->id,
        );

        $order->update(['wallet_amount_charged' => '0.00']);
    }

    private function recordStatusChange(
        Order $order,
        ?OrderStatus $fromStatus,
        OrderStatus $toStatus,
        ?int $changedBy,
        ?string $notes,
    ): void {
        OrderStatusHistory::query()->create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $changedBy,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }

    private function walletDebitPolicy(Tenant $tenant): string
    {
        $policy = $tenant->settings['wallet_debit_policy'] ?? 'on_confirm';

        return in_array($policy, ['on_confirm', 'on_delivery'], true) ? $policy : 'on_confirm';
    }
}
