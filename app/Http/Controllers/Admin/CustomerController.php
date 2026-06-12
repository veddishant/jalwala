<?php

namespace App\Http\Controllers\Admin;

use App\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CustomerOnboardingService;
use App\Services\DepositService;
use App\Support\TenantContext;
use App\TenantStatus;
use App\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerOnboardingService $onboardingService,
        private DepositService $depositService,
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
     * @return Builder<Customer>
     */
    private function tenantCustomersQuery(Request $request): Builder
    {
        return Customer::query()->where('tenant_id', $this->currentTenantId($request));
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Customer::class);

        $search = $request->string('search')->trim()->toString();

        $customers = $this->tenantCustomersQuery($request)
            ->with('defaultAddress')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->filled('status'),
                fn (Builder $query) => $query->where('status', $request->string('status')->toString()),
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Customer $customer): array => $this->transformCustomer($customer));

        return Inertia::render('admin/customers/index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $request->string('status')->toString(),
            ],
            'statuses' => $this->statusOptions(),
            'can' => [
                'create' => $request->user()?->can('customers.create') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->ensureTenantContext($request);
        $this->authorize('create', Customer::class);

        return Inertia::render('admin/customers/create', [
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $tenantId = $this->currentTenantId($request);

        $this->onboardingService->onboard([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'status' => $request->validated('status'),
            'notes' => $request->validated('notes'),
            'address' => $request->validated('address'),
            'portal' => [
                'create' => $request->boolean('portal.create'),
                'password' => $request->validated('portal.password'),
            ],
            'wallet' => [
                'opening_balance' => $request->validated('wallet.opening_balance'),
                'low_balance_threshold' => $request->validated('wallet.low_balance_threshold'),
            ],
        ], $tenantId, (int) $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer created successfully.')]);

        return to_route('admin.customers.index');
    }

    public function edit(Request $request, int $managedCustomer): Response
    {
        $customer = $this->tenantCustomersQuery($request)
            ->with(['defaultAddress', 'user'])
            ->findOrFail($managedCustomer);

        $this->authorize('update', $customer);

        return Inertia::render('admin/customers/edit', [
            'customer' => $this->transformCustomer($customer, includeAddress: true),
            'statuses' => $this->statusOptions(),
            'can' => [
                'manageAddresses' => $request->user()?->can('manageAddresses', $customer) ?? false,
                'createPortal' => $customer->user_id === null && filled($customer->email),
            ],
        ]);
    }

    public function update(UpdateCustomerRequest $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)
            ->with('defaultAddress')
            ->findOrFail($managedCustomer);

        $customer->fill([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'status' => $request->validated('status'),
            'notes' => $request->validated('notes'),
        ]);

        if ($customer->status === CustomerStatus::Closed && $customer->closed_at === null) {
            $customer->closed_at = now();
        }

        if ($customer->status !== CustomerStatus::Closed) {
            $customer->closed_at = null;
            $customer->closure_reason = null;
        }

        $customer->save();

        if ($request->user()?->can('manageAddresses', $customer)) {
            $this->syncDefaultAddress($customer, $request->validated('address'));
        }

        if ($request->boolean('portal.create') && $customer->user_id === null && filled($customer->email)) {
            $this->createPortalUser($customer, $request->validated('portal.password'));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer updated successfully.')]);

        return to_route('admin.customers.index');
    }

    public function pause(Request $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);

        $this->authorize('pause', $customer);

        $customer->update(['status' => CustomerStatus::Paused]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer paused successfully.')]);

        return back();
    }

    public function resume(Request $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);

        $this->authorize('resume', $customer);

        $customer->update(['status' => CustomerStatus::Active]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer resumed successfully.')]);

        return back();
    }

    public function close(Request $request, int $managedCustomer): RedirectResponse
    {
        $request->validate([
            'closure_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer = $this->tenantCustomersQuery($request)->with('user')->findOrFail($managedCustomer);

        $this->authorize('close', $customer);

        $customer->update([
            'status' => CustomerStatus::Closed,
            'closed_at' => now(),
            'closure_reason' => $request->string('closure_reason')->toString() ?: null,
        ]);

        if ($customer->user !== null) {
            $customer->user->update(['status' => UserStatus::Inactive]);
        }

        $deposit = $this->depositService->ensureForCustomer($customer);
        $this->depositService->refundAll(
            deposit: $deposit,
            createdBy: (int) $request->user()->id,
            description: 'Full deposit refund on customer closure',
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer closed successfully.')]);

        return to_route('admin.customers.index');
    }

    /**
     * @param  array<string, mixed>  $addressData
     */
    private function syncDefaultAddress(Customer $customer, array $addressData): void
    {
        $address = $customer->defaultAddress;

        if ($address === null) {
            CustomerAddress::query()->create([
                ...$addressData,
                'customer_id' => $customer->id,
                'is_default' => true,
            ]);

            return;
        }

        $address->update($addressData);
    }

    private function createPortalUser(Customer $customer, string $password): void
    {
        $user = User::query()->create([
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'password' => Hash::make($password),
            'status' => UserStatus::Active,
        ]);

        $user->forceFill(['tenant_id' => $customer->tenant_id])->save();
        $user->assignRole('customer');

        $customer->update(['user_id' => $user->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCustomer(Customer $customer, bool $includeAddress = false): array
    {
        $data = [
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'status' => $customer->status->value,
            'notes' => $customer->notes,
            'has_portal_account' => $customer->user_id !== null,
            'closed_at' => $customer->closed_at?->toISOString(),
            'closure_reason' => $customer->closure_reason,
            'created_at' => $customer->created_at?->toISOString(),
        ];

        if ($includeAddress && $customer->relationLoaded('defaultAddress') && $customer->defaultAddress !== null) {
            $data['address'] = $this->transformAddress($customer->defaultAddress);
        } elseif ($includeAddress) {
            $data['address'] = null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformAddress(CustomerAddress $address): array
    {
        return [
            'label' => $address->label,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'delivery_instructions' => $address->delivery_instructions,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(CustomerStatus::cases())
            ->reject(fn (CustomerStatus $status): bool => $status === CustomerStatus::Closed)
            ->map(fn (CustomerStatus $status): array => [
                'value' => $status->value,
                'label' => str($status->value)->headline()->toString(),
            ])
            ->values()
            ->all();
    }
}
