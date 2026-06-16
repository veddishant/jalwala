<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreTenantRequest;
use App\Http\Requests\Platform\UpdateTenantRequest;
use App\Http\Requests\Platform\UpdateTenantSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantOnboardingService;
use App\TenantStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(
        private TenantOnboardingService $onboardingService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Tenant::class);

        $search = $request->string('search')->trim()->toString();

        $tenants = Tenant::query()
            ->with('subscription')
            ->withCount('users', 'customers')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tenant $tenant): array => $this->tenantListItem($tenant, $request));

        return Inertia::render('platform/tenants/index', [
            'tenants' => $tenants,
            'filters' => [
                'search' => $search,
            ],
            'can' => [
                'create' => $request->user()?->can('platform.tenants.create') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Tenant::class);

        return Inertia::render('platform/tenants/create', [
            'defaults' => [
                'timezone' => 'Asia/Kolkata',
                'currency' => 'INR',
            ],
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $result = $this->onboardingService->onboard([
            'business_name' => $validated['business_name'],
            'slug' => $validated['slug'] ?? null,
            'timezone' => $validated['timezone'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'admin' => [
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'phone' => $validated['admin_phone'] ?? null,
                'password' => $validated['admin_password'],
            ],
        ]);

        return to_route('platform.tenants.show', $result['tenant'])
            ->with('status', 'Tenant created successfully.');
    }

    public function show(Request $request, Tenant $tenant): Response
    {
        $this->authorize('view', $tenant);

        $tenant->load(['subscription', 'users.roles']);

        return Inertia::render('platform/tenants/show', [
            'tenant' => $this->tenantDetail($tenant),
            'can' => [
                'update' => $request->user()?->can('update', $tenant) ?? false,
                'suspend' => $request->user()?->can('suspend', $tenant) ?? false,
                'impersonate' => $request->user()?->can('impersonate', $tenant) ?? false,
            ],
        ]);
    }

    public function edit(Request $request, Tenant $tenant): Response
    {
        $this->authorize('update', $tenant);

        return Inertia::render('platform/tenants/edit', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'timezone' => $tenant->timezone,
                'currency' => $tenant->currency,
                'status' => $tenant->status->value,
            ],
            'statuses' => collect(TenantStatus::cases())->map(fn (TenantStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($request->validated());

        return to_route('platform.tenants.show', $tenant)
            ->with('status', 'Tenant updated successfully.');
    }

    public function editSettings(Request $request, Tenant $tenant): Response
    {
        $this->authorize('update', $tenant);

        $settings = $this->onboardingService->mergeSettings($tenant->settings ?? []);

        return Inertia::render('platform/tenants/settings', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'settings' => [
                'branding' => $settings['branding'],
                'notifications' => $settings['notifications'],
                'domain' => $settings['domain'],
            ],
            'customDomainsEnabled' => (bool) config('tenancy.custom_domains.enabled'),
        ]);
    }

    public function updateSettings(UpdateTenantSettingsRequest $request, Tenant $tenant): RedirectResponse
    {
        $current = $this->onboardingService->mergeSettings($tenant->settings ?? []);
        $validated = $request->validated();

        $tenant->update([
            'settings' => array_replace_recursive($current, $validated),
        ]);

        return to_route('platform.tenants.settings.edit', $tenant)
            ->with('status', 'Tenant settings updated successfully.');
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('suspend', $tenant);

        $tenant->update(['status' => TenantStatus::Suspended]);

        return back()->with('status', 'Tenant suspended.');
    }

    public function activate(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('suspend', $tenant);

        $tenant->update(['status' => TenantStatus::Active]);

        return back()->with('status', 'Tenant reactivated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantListItem(Tenant $tenant, Request $request): array
    {
        return [
            ...$this->tenantSummary($tenant),
            'users_count' => $tenant->users_count,
            'customers_count' => $tenant->customers_count,
            'can' => [
                'impersonate' => $request->user()?->can('impersonate', $tenant) ?? false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantSummary(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'status' => $tenant->status->value,
            'status_label' => $tenant->status->label(),
            'timezone' => $tenant->timezone,
            'currency' => $tenant->currency,
            'plan' => $tenant->subscription?->plan,
            'subscription_status' => $tenant->subscription?->status?->value,
            'subscription_status_label' => $tenant->subscription?->status?->label(),
            'trial_ends_at' => $tenant->subscription?->trial_ends_at?->toIso8601String(),
            'created_at' => $tenant->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantDetail(Tenant $tenant): array
    {
        return [
            ...$this->tenantSummary($tenant),
            'settings' => $this->onboardingService->mergeSettings($tenant->settings ?? []),
            'users_count' => $tenant->users()->count(),
            'customers_count' => $tenant->customers()->count(),
            'admins' => $tenant->users
                ->filter(fn ($user): bool => $user->hasRole('supplier-admin'))
                ->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->values()
                ->all(),
        ];
    }
}
