<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\CustomerInventoryService;
use App\Services\InventoryService;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerInventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private CustomerInventoryService $customerInventoryService,
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

    public function show(Request $request, int $managedCustomer): Response
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);
        $location = $this->inventoryService->ensureCustomerLocation($customer);

        $this->authorize('view', $location);

        $movements = $this->customerInventoryService
            ->movementsForCustomer($customer)
            ->through(fn (InventoryMovement $movement): array => $this->transformMovement($movement));

        return Inertia::render('admin/customers/inventory', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
            ],
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
            ],
            'summary' => $this->customerInventoryService->summaryForCustomer($customer),
            'products' => $this->inventoryService->returnableProductsForTenant($this->currentTenantId($request)),
            'movements' => $movements,
            'can' => [
                'adjust' => $request->user()?->can('adjust', $location) ?? false,
            ],
        ]);
    }

    public function adjust(AdjustInventoryRequest $request, int $managedCustomer): RedirectResponse
    {
        $customer = $this->tenantCustomersQuery($request)->findOrFail($managedCustomer);
        $location = $this->inventoryService->ensureCustomerLocation($customer);

        $this->authorize('adjust', $location);

        $product = Product::query()
            ->where('tenant_id', $customer->tenant_id)
            ->findOrFail($request->validated('product_id'));

        $this->inventoryService->adjust(
            location: $location,
            product: $product,
            jarType: $request->validated('jar_type'),
            direction: $request->validated('direction'),
            quantity: (int) $request->validated('quantity'),
            reason: $request->validated('reason'),
            createdBy: (int) $request->user()->id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer inventory adjusted successfully.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformMovement(InventoryMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'movement_type' => $movement->movement_type->value,
            'movement_label' => $movement->movement_type->label(),
            'quantity' => $movement->quantity,
            'product_name' => $movement->product?->name,
            'notes' => $movement->notes,
            'reference_type' => $movement->reference_type,
            'created_by' => $movement->createdBy?->name,
            'created_at' => $movement->created_at?->toISOString(),
        ];
    }
}
