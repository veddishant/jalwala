<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\CancelOrderRequest;
use App\Http\Requests\Portal\StoreOrderRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\OrderSource;
use App\ProductStatus;
use App\Services\OrderService;
use App\Services\WalletService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private WalletService $walletService,
    ) {}

    public function index(Request $request): Response
    {
        $customer = $this->resolveCustomer($request);

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['items'])
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'uuid' => $order->uuid,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'total' => $order->total,
                'scheduled_date' => $order->scheduled_date->toDateString(),
                'item_count' => $order->items->sum('quantity'),
                'created_at' => $order->created_at?->toISOString(),
            ]);

        return Inertia::render('portal/orders/index', [
            'orders' => $orders,
            'can' => [
                'create' => $request->user()?->can('create', Order::class) ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Order::class);

        $customer = $this->resolveCustomer($request);

        if ($customer->isClosed()) {
            abort(403, 'Your account is closed.');
        }

        $wallet = $this->walletService->ensureForCustomer($customer);

        $products = Product::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('status', ProductStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit_price', 'capacity_liters'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => $product->unit_price,
                'capacity_liters' => $product->capacity_liters,
            ])
            ->all();

        $addresses = $customer->addresses()
            ->orderByDesc('is_default')
            ->get(['id', 'label', 'address_line_1', 'city', 'is_default'])
            ->map(fn (CustomerAddress $address): array => [
                'id' => $address->id,
                'label' => $address->label,
                'address_line_1' => $address->address_line_1,
                'city' => $address->city,
                'is_default' => $address->is_default,
            ])
            ->all();

        return Inertia::render('portal/orders/create', [
            'products' => $products,
            'addresses' => $addresses,
            'wallet' => [
                'balance' => $wallet->balance,
            ],
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $customer = $this->resolveCustomer($request);

        if ($customer->isClosed()) {
            abort(403, 'Your account is closed.');
        }

        $addressId = $request->validated('customer_address_id')
            ?? $customer->defaultAddress?->id
            ?? $customer->addresses()->value('id');

        if ($addressId === null) {
            return back()->withErrors(['customer_address_id' => __('A delivery address is required.')]);
        }

        $address = CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->findOrFail($addressId);

        $order = $this->orderService->create(
            customer: $customer,
            address: $address,
            items: $request->validated('items'),
            scheduledDate: $request->validated('scheduled_date'),
            source: OrderSource::CustomerPortal,
            createdBy: (int) $request->user()->id,
            notes: $request->validated('notes'),
        );

        $this->orderService->confirm($order, (int) $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order placed successfully.')]);

        return to_route('portal.orders.show', $order);
    }

    public function show(Request $request, Order $managedOrder): Response
    {
        $customer = $this->resolveCustomer($request);

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('uuid', $managedOrder->uuid)
            ->with([
                'address',
                'items.product:id,name,sku',
                'statusHistories.changedBy:id,name',
            ])
            ->firstOrFail();

        $this->authorize('view', $order);

        return Inertia::render('portal/orders/show', [
            'order' => [
                'uuid' => $order->uuid,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'total' => $order->total,
                'wallet_amount_charged' => $order->wallet_amount_charged,
                'scheduled_date' => $order->scheduled_date->toDateString(),
                'notes' => $order->notes,
                'cancellation_reason' => $order->cancellation_reason,
                'created_at' => $order->created_at?->toISOString(),
                'address' => [
                    'label' => $order->address?->label,
                    'address_line_1' => $order->address?->address_line_1,
                    'city' => $order->address?->city,
                    'postal_code' => $order->address?->postal_code,
                ],
                'items' => $order->items->map(fn ($item): array => [
                    'product_name' => $item->product?->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ])->values()->all(),
                'timeline' => $order->statusHistories->map(fn ($history): array => [
                    'to_status' => $history->to_status->value,
                    'to_status_label' => $history->to_status->label(),
                    'notes' => $history->notes,
                    'created_at' => $history->created_at?->toISOString(),
                ])->values()->all(),
            ],
            'can' => [
                'cancel' => $request->user()?->can('cancel', $order) ?? false,
            ],
        ]);
    }

    public function cancel(CancelOrderRequest $request, Order $managedOrder): RedirectResponse
    {
        $customer = $this->resolveCustomer($request);

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('uuid', $managedOrder->uuid)
            ->firstOrFail();

        $this->authorize('cancel', $order);

        $this->orderService->cancel(
            order: $order,
            cancelledBy: (int) $request->user()->id,
            reason: $request->validated('cancellation_reason'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order cancelled.')]);

        return back();
    }

    private function resolveCustomer(Request $request): Customer
    {
        TenantContext::resolveFromAuthenticatedUser($request->user());

        $customer = $request->user()?->customer;

        if ($customer === null) {
            abort(404);
        }

        return $customer;
    }
}
