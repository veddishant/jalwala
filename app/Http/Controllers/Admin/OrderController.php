<?php

namespace App\Http\Controllers\Admin;

use App\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Http\Requests\Admin\TransitionOrderRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\OrderSource;
use App\OrderStatus;
use App\ProductStatus;
use App\Services\OrderService;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    private function ensureTenantContext(Request $request): void
    {
        TenantContext::resolveFromAuthenticatedUser($request->user());

        if (TenantContext::getId() !== null || TenantContext::isBypassed()) {
            return;
        }

        $user = $request->user();

        if ($user?->hasRole('super-admin') && $request->hasSession()) {
            try {
                $activeTenantId = $request->session()->get('active_tenant_id');

                if ($activeTenantId !== null) {
                    TenantContext::setId((int) $activeTenantId);

                    return;
                }
            } catch (\RuntimeException) {
                //
            }
        }

        if ($user?->hasRole('super-admin') && TenantContext::getId() === null) {
            $activeTenantCount = Tenant::query()
                ->where('status', TenantStatus::Active)
                ->count();

            if ($activeTenantCount === 1) {
                $tenantId = Tenant::query()
                    ->where('status', TenantStatus::Active)
                    ->orderBy('id')
                    ->value('id');

                if ($tenantId !== null) {
                    TenantContext::setId((int) $tenantId);
                }
            }
        }
    }

    private function currentTenantId(Request $request): int
    {
        $this->ensureTenantContext($request);

        $tenantId = TenantContext::getId() ?? $request->user()?->tenant_id;

        if ($tenantId === null) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }

    /**
     * @return Builder<Order>
     */
    private function tenantOrdersQuery(Request $request): Builder
    {
        return Order::query()->where('tenant_id', $this->currentTenantId($request));
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $orders = $this->tenantOrdersQuery($request)
            ->with(['customer:id,name,code', 'items'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'customer',
                fn (Builder $customerQuery) => $customerQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%"),
            ))
            ->when(
                $status !== '' && in_array($status, array_column(OrderStatus::cases(), 'value'), true),
                fn (Builder $query) => $query->where('status', $status),
            )
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order): array => $this->transformOrderSummary($order));

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $orderStatus): array => [
                    'value' => $orderStatus->value,
                    'label' => $orderStatus->label(),
                ])
                ->values()
                ->all(),
            'can' => [
                'create' => $request->user()?->can('create', Order::class) ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Order::class);

        $tenantId = $this->currentTenantId($request);

        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->where('status', CustomerStatus::Active)
            ->with(['addresses'])
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
                'addresses' => $customer->addresses->map(fn (CustomerAddress $address): array => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'address_line_1' => $address->address_line_1,
                    'city' => $address->city,
                    'is_default' => $address->is_default,
                ])->values()->all(),
            ])
            ->all();

        $products = Product::query()
            ->where('tenant_id', $tenantId)
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

        return Inertia::render('admin/orders/create', [
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $tenantId = $this->currentTenantId($request);

        $customer = Customer::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($request->validated('customer_id'));

        $address = CustomerAddress::query()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->findOrFail($request->validated('customer_address_id'));

        $order = $this->orderService->create(
            customer: $customer,
            address: $address,
            items: $request->validated('items'),
            scheduledDate: $request->validated('scheduled_date'),
            source: OrderSource::Manual,
            createdBy: (int) $request->user()->id,
            notes: $request->validated('notes'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order created as draft.')]);

        return to_route('admin.orders.show', $order);
    }

    public function show(Request $request, Order $managedOrder): Response
    {
        $order = $this->tenantOrdersQuery($request)
            ->where('uuid', $managedOrder->uuid)
            ->with([
                'customer:id,name,code,phone',
                'address',
                'items.product:id,name,sku',
                'statusHistories.changedBy:id,name',
                'createdBy:id,name',
            ])
            ->firstOrFail();

        $this->authorize('view', $order);

        $nextStatuses = collect($this->orderService->allowedTransitions($order->status))
            ->reject(fn (OrderStatus $status): bool => $status === OrderStatus::Cancelled)
            ->map(fn (OrderStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/orders/show', [
            'order' => $this->transformOrderDetail($order),
            'nextStatuses' => $nextStatuses,
            'can' => [
                'confirm' => $request->user()?->can('confirm', $order) ?? false,
                'cancel' => $request->user()?->can('cancel', $order) ?? false,
                'transition' => $request->user()?->can('transition', $order) ?? false,
            ],
        ]);
    }

    public function confirm(Request $request, Order $managedOrder): RedirectResponse
    {
        $order = $this->tenantOrdersQuery($request)
            ->where('uuid', $managedOrder->uuid)
            ->firstOrFail();

        $this->authorize('confirm', $order);

        $this->orderService->confirm($order, (int) $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order confirmed and wallet charged.')]);

        return back();
    }

    public function cancel(CancelOrderRequest $request, Order $managedOrder): RedirectResponse
    {
        $order = $this->tenantOrdersQuery($request)
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

    public function transition(TransitionOrderRequest $request, Order $managedOrder): RedirectResponse
    {
        $order = $this->tenantOrdersQuery($request)
            ->where('uuid', $managedOrder->uuid)
            ->firstOrFail();

        $this->authorize('transition', $order);

        $toStatus = OrderStatus::from($request->validated('status'));

        if (! $this->orderService->canTransition($order->status, $toStatus)) {
            return back()->withErrors(['status' => __('Invalid status transition.')]);
        }

        $this->orderService->transition(
            order: $order,
            toStatus: $toStatus,
            changedBy: (int) $request->user()->id,
            notes: $request->validated('notes'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order status updated.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrderSummary(Order $order): array
    {
        return [
            'uuid' => $order->uuid,
            'customer_name' => $order->customer?->name,
            'customer_code' => $order->customer?->code,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'source' => $order->source->value,
            'total' => $order->total,
            'scheduled_date' => $order->scheduled_date->toDateString(),
            'item_count' => $order->items->sum('quantity'),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrderDetail(Order $order): array
    {
        return [
            'uuid' => $order->uuid,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'source' => $order->source->value,
            'source_label' => $order->source->label(),
            'subtotal' => $order->subtotal,
            'total' => $order->total,
            'wallet_amount_charged' => $order->wallet_amount_charged,
            'scheduled_date' => $order->scheduled_date->toDateString(),
            'delivered_at' => $order->delivered_at?->toISOString(),
            'cancelled_at' => $order->cancelled_at?->toISOString(),
            'cancellation_reason' => $order->cancellation_reason,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toISOString(),
            'created_by' => $order->createdBy?->name,
            'customer' => [
                'id' => $order->customer?->id,
                'name' => $order->customer?->name,
                'code' => $order->customer?->code,
                'phone' => $order->customer?->phone,
            ],
            'address' => [
                'label' => $order->address?->label,
                'address_line_1' => $order->address?->address_line_1,
                'address_line_2' => $order->address?->address_line_2,
                'city' => $order->address?->city,
                'state' => $order->address?->state,
                'postal_code' => $order->address?->postal_code,
                'delivery_instructions' => $order->address?->delivery_instructions,
            ],
            'items' => $order->items->map(fn ($item): array => [
                'product_name' => $item->product?->name,
                'product_sku' => $item->product?->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->values()->all(),
            'timeline' => $order->statusHistories->map(fn ($history): array => [
                'from_status' => $history->from_status?->value,
                'from_status_label' => $history->from_status?->label(),
                'to_status' => $history->to_status->value,
                'to_status_label' => $history->to_status->label(),
                'notes' => $history->notes,
                'changed_by' => $history->changedBy?->name,
                'created_at' => $history->created_at?->toISOString(),
            ])->values()->all(),
        ];
    }
}
