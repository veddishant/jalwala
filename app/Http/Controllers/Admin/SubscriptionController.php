<?php

namespace App\Http\Controllers\Admin;

use App\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PauseSubscriptionRequest;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionSchedule;
use App\Models\Tenant;
use App\ProductStatus;
use App\Services\SubscriptionPauseService;
use App\Services\SubscriptionService;
use App\SubscriptionStatus;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private SubscriptionPauseService $pauseService,
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
     * @return Builder<Subscription>
     */
    private function tenantSubscriptionsQuery(Request $request): Builder
    {
        return Subscription::query()->where('tenant_id', $this->currentTenantId($request));
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Subscription::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $subscriptions = $this->tenantSubscriptionsQuery($request)
            ->with(['customer:id,name,code', 'schedules', 'items'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'customer',
                fn (Builder $customerQuery) => $customerQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%"),
            ))
            ->when(
                $status !== '' && in_array($status, array_column(SubscriptionStatus::cases(), 'value'), true),
                fn (Builder $query) => $query->where('status', $status),
            )
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Subscription $subscription): array => $this->transformSummary($subscription));

        return Inertia::render('admin/subscriptions/index', [
            'subscriptions' => $subscriptions,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => collect(SubscriptionStatus::cases())
                ->map(fn (SubscriptionStatus $s): array => [
                    'value' => $s->value,
                    'label' => $s->label(),
                ])
                ->values()
                ->all(),
            'can' => [
                'create' => $request->user()?->can('create', Subscription::class) ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Subscription::class);

        $tenantId = $this->currentTenantId($request);

        return Inertia::render('admin/subscriptions/create', [
            'customers' => $this->customerOptions($tenantId),
            'products' => $this->productOptions($tenantId),
            'daysOfWeek' => $this->dayOptions(),
        ]);
    }

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $tenantId = $this->currentTenantId($request);

        $customer = Customer::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($request->validated('customer_id'));

        $address = CustomerAddress::query()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->findOrFail($request->validated('customer_address_id'));

        $subscription = $this->subscriptionService->create(
            customer: $customer,
            address: $address,
            items: $request->validated('items'),
            daysOfWeek: $request->validated('days_of_week'),
            startDate: $request->validated('start_date'),
            notes: $request->validated('notes'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscription created successfully.')]);

        return to_route('admin.subscriptions.show', $subscription);
    }

    public function show(Request $request, Subscription $managedSubscription): Response
    {
        $subscription = $this->loadSubscription($request, $managedSubscription);

        $this->authorize('view', $subscription);

        return Inertia::render('admin/subscriptions/show', [
            'subscription' => $this->transformDetail($subscription),
            'upcomingDeliveries' => $this->subscriptionService->upcomingDeliveryDates($subscription),
            'can' => [
                'update' => $request->user()?->can('update', $subscription) ?? false,
                'pause' => $request->user()?->can('pause', $subscription) ?? false,
                'resume' => $request->user()?->can('resume', $subscription) ?? false,
                'cancel' => $request->user()?->can('cancel', $subscription) ?? false,
            ],
        ]);
    }

    public function edit(Request $request, Subscription $managedSubscription): Response
    {
        $subscription = $this->loadSubscription($request, $managedSubscription);

        $this->authorize('update', $subscription);

        $tenantId = $this->currentTenantId($request);

        return Inertia::render('admin/subscriptions/edit', [
            'subscription' => $this->transformDetail($subscription),
            'customers' => $this->customerOptions($tenantId),
            'products' => $this->productOptions($tenantId),
            'daysOfWeek' => $this->dayOptions(),
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $managedSubscription): RedirectResponse
    {
        $subscription = $this->loadSubscription($request, $managedSubscription);

        $this->authorize('update', $subscription);

        $tenantId = $this->currentTenantId($request);

        $address = CustomerAddress::query()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $subscription->customer_id)
            ->findOrFail($request->validated('customer_address_id'));

        $this->subscriptionService->update(
            subscription: $subscription,
            address: $address,
            items: $request->validated('items'),
            daysOfWeek: $request->validated('days_of_week'),
            notes: $request->validated('notes'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscription updated successfully.')]);

        return to_route('admin.subscriptions.show', $subscription);
    }

    public function pause(PauseSubscriptionRequest $request, Subscription $managedSubscription): RedirectResponse
    {
        $subscription = $this->loadSubscription($request, $managedSubscription);

        $this->authorize('pause', $subscription);

        $this->pauseService->pause(
            subscription: $subscription,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            createdBy: (int) $request->user()->id,
            reason: $request->validated('reason'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscription paused.')]);

        return back();
    }

    public function resume(Request $request, Subscription $managedSubscription): RedirectResponse
    {
        $subscription = $this->loadSubscription($request, $managedSubscription);

        $this->authorize('resume', $subscription);

        $this->pauseService->resume($subscription);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscription resumed.')]);

        return back();
    }

    public function cancel(Request $request, Subscription $managedSubscription): RedirectResponse
    {
        $subscription = $this->loadSubscription($request, $managedSubscription);

        $this->authorize('cancel', $subscription);

        $this->subscriptionService->cancel($subscription);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Subscription cancelled.')]);

        return to_route('admin.subscriptions.index');
    }

    private function loadSubscription(Request $request, Subscription $managedSubscription): Subscription
    {
        return $this->tenantSubscriptionsQuery($request)
            ->where('id', $managedSubscription->id)
            ->with(['customer', 'address', 'items.product', 'schedules', 'pauses'])
            ->firstOrFail();
    }

    /**
     * @return list<array{value: int, label: string, short: string}>
     */
    private function dayOptions(): array
    {
        return collect(range(0, 6))
            ->map(fn (int $day): array => [
                'value' => $day,
                'label' => SubscriptionSchedule::dayLabel($day),
                'short' => SubscriptionSchedule::shortDayLabel($day),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customerOptions(int $tenantId): array
    {
        return Customer::query()
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
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productOptions(int $tenantId): array
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->where('status', ProductStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit_price'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => $product->unit_price,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformSummary(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'customer_name' => $subscription->customer?->name,
            'customer_code' => $subscription->customer?->code,
            'status' => $subscription->status->value,
            'status_label' => $subscription->status->label(),
            'start_date' => $subscription->start_date->toDateString(),
            'schedule_days' => $subscription->schedules
                ->map(fn ($schedule) => SubscriptionSchedule::shortDayLabel($schedule->day_of_week))
                ->join(', '),
            'weekly_total' => $this->weeklyTotal($subscription),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformDetail(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'status' => $subscription->status->value,
            'status_label' => $subscription->status->label(),
            'start_date' => $subscription->start_date->toDateString(),
            'paused_until' => $subscription->paused_until?->toDateString(),
            'notes' => $subscription->notes,
            'customer' => [
                'id' => $subscription->customer?->id,
                'name' => $subscription->customer?->name,
                'code' => $subscription->customer?->code,
            ],
            'address' => [
                'id' => $subscription->address?->id,
                'label' => $subscription->address?->label,
                'address_line_1' => $subscription->address?->address_line_1,
                'city' => $subscription->address?->city,
            ],
            'items' => $subscription->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => bcmul($item->unit_price, (string) $item->quantity, 2),
            ])->values()->all(),
            'days_of_week' => $subscription->schedules
                ->pluck('day_of_week')
                ->map(fn ($day): int => (int) $day)
                ->values()
                ->all(),
            'pauses' => $subscription->pauses->map(fn ($pause): array => [
                'start_date' => $pause->start_date->toDateString(),
                'end_date' => $pause->end_date->toDateString(),
                'reason' => $pause->reason,
            ])->values()->all(),
            'weekly_total' => $this->weeklyTotal($subscription),
        ];
    }

    private function weeklyTotal(Subscription $subscription): string
    {
        return $subscription->items->reduce(
            fn (string $carry, $item): string => bcadd(
                $carry,
                bcmul($item->unit_price, (string) $item->quantity, 2),
                2,
            ),
            '0.00',
        );
    }
}
