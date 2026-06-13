<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Http\Requests\Admin\ReceiveStockRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\InventoryService;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
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

    private function currentTenant(Request $request): Tenant
    {
        $this->ensureTenantContext($request);

        $tenantId = TenantContext::getId() ?? $request->user()?->tenant_id;

        if ($tenantId === null) {
            abort(403, 'Tenant context is required.');
        }

        return Tenant::query()->findOrFail($tenantId);
    }

    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant($request);
        $warehouse = $this->inventoryService->ensureWarehouseLocation($tenant);

        $this->authorize('view', $warehouse);

        $balances = $this->inventoryService->warehouseBalances($tenant);

        $movements = InventoryMovement::query()
            ->where('inventory_location_id', $warehouse->id)
            ->with(['product:id,name', 'createdBy:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (InventoryMovement $movement): array => $this->transformMovement($movement));

        return Inertia::render('admin/inventory/index', [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ],
            'balances' => $balances->map(fn ($balance): array => [
                'id' => $balance->id,
                'product_id' => $balance->product_id,
                'product_name' => $balance->product->name,
                'sku' => $balance->product->sku,
                'capacity_liters' => $balance->product->capacity_liters,
                'filled_quantity' => $balance->filled_quantity,
                'empty_quantity' => $balance->empty_quantity,
            ]),
            'products' => $this->inventoryService->returnableProductsForTenant($tenant->id),
            'movements' => $movements,
            'can' => [
                'adjust' => $request->user()?->can('adjust', $warehouse) ?? false,
                'receiveStock' => $request->user()?->can('receiveStock', $warehouse) ?? false,
            ],
        ]);
    }

    public function receiveStock(ReceiveStockRequest $request): RedirectResponse
    {
        $tenant = $this->currentTenant($request);
        $warehouse = $this->inventoryService->ensureWarehouseLocation($tenant);

        $this->authorize('receiveStock', $warehouse);

        $product = Product::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($request->validated('product_id'));

        $this->inventoryService->receiveStock(
            location: $warehouse,
            product: $product,
            quantity: (int) $request->validated('quantity'),
            createdBy: (int) $request->user()->id,
            notes: $request->validated('notes'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock received successfully.')]);

        return back();
    }

    public function adjust(AdjustInventoryRequest $request): RedirectResponse
    {
        $tenant = $this->currentTenant($request);
        $warehouse = $this->inventoryService->ensureWarehouseLocation($tenant);

        $this->authorize('adjust', $warehouse);

        $product = Product::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($request->validated('product_id'));

        $this->inventoryService->adjust(
            location: $warehouse,
            product: $product,
            jarType: $request->validated('jar_type'),
            direction: $request->validated('direction'),
            quantity: (int) $request->validated('quantity'),
            reason: $request->validated('reason'),
            createdBy: (int) $request->user()->id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inventory adjusted successfully.')]);

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
