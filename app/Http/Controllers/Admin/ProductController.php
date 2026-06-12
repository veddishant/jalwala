<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductPriceRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\Tenant;
use App\ProductStatus;
use App\ProductType;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
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
     * @return Builder<Product>
     */
    private function tenantProductsQuery(Request $request): Builder
    {
        return Product::query()->where('tenant_id', $this->currentTenantId($request));
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $search = $request->string('search')->trim()->toString();

        $products = $this->tenantProductsQuery($request)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->filled('status'),
                fn (Builder $query) => $query->where('status', $request->string('status')->toString()),
            )
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => $this->transformProduct($product));

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'status' => $request->string('status')->toString(),
            ],
            'statuses' => $this->statusOptions(),
            'types' => $this->typeOptions(),
            'can' => [
                'create' => $request->user()?->can('products.create') ?? false,
                'update' => $request->user()?->can('products.update') ?? false,
                'deactivate' => $request->user()?->can('products.deactivate') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->ensureTenantContext($request);
        $this->authorize('create', Product::class);

        return Inertia::render('admin/products/create', [
            'statuses' => $this->statusOptions(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->currentTenantId($request);

        Product::query()->create([
            'name' => $request->validated('name'),
            'sku' => $request->validated('sku'),
            'type' => $request->validated('type'),
            'capacity_liters' => $request->validated('capacity_liters'),
            'unit_price' => $request->validated('unit_price'),
            'deposit_amount' => $request->validated('deposit_amount'),
            'is_returnable' => $request->boolean('is_returnable'),
            'status' => $request->validated('status'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created successfully.')]);

        return to_route('admin.products.index');
    }

    public function edit(Request $request, int $managedProduct): Response
    {
        $product = $this->tenantProductsQuery($request)->findOrFail($managedProduct);

        $this->authorize('update', $product);

        return Inertia::render('admin/products/edit', [
            'product' => $this->transformProduct($product),
            'statuses' => $this->statusOptions(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, int $managedProduct): RedirectResponse
    {
        $product = $this->tenantProductsQuery($request)->findOrFail($managedProduct);

        $this->authorize('update', $product);

        $product->update([
            'name' => $request->validated('name'),
            'sku' => $request->validated('sku'),
            'type' => $request->validated('type'),
            'capacity_liters' => $request->validated('capacity_liters'),
            'unit_price' => $request->validated('unit_price'),
            'deposit_amount' => $request->validated('deposit_amount'),
            'is_returnable' => $request->boolean('is_returnable'),
            'status' => $request->validated('status'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated successfully.')]);

        return to_route('admin.products.index');
    }

    public function updatePrice(UpdateProductPriceRequest $request, int $managedProduct): RedirectResponse
    {
        $product = $this->tenantProductsQuery($request)->findOrFail($managedProduct);

        $this->authorize('update', $product);

        $product->update([
            'unit_price' => $request->validated('unit_price'),
            'deposit_amount' => $request->validated('deposit_amount'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prices updated successfully.')]);

        return back();
    }

    public function deactivate(Request $request, int $managedProduct): RedirectResponse
    {
        $product = $this->tenantProductsQuery($request)->findOrFail($managedProduct);

        $this->authorize('deactivate', $product);

        $product->update(['status' => ProductStatus::Inactive]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deactivated successfully.')]);

        return back();
    }

    public function activate(Request $request, int $managedProduct): RedirectResponse
    {
        $product = $this->tenantProductsQuery($request)->findOrFail($managedProduct);

        $this->authorize('deactivate', $product);

        $product->update(['status' => ProductStatus::Active]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product activated successfully.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->type->value,
            'capacity_liters' => $product->capacity_liters,
            'unit_price' => $product->unit_price,
            'deposit_amount' => $product->deposit_amount,
            'is_returnable' => $product->is_returnable,
            'status' => $product->status->value,
            'created_at' => $product->created_at?->toISOString(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(ProductStatus::cases())
            ->map(fn (ProductStatus $status): array => [
                'value' => $status->value,
                'label' => str($status->value)->headline()->toString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return collect(ProductType::cases())
            ->map(fn (ProductType $type): array => [
                'value' => $type->value,
                'label' => str($type->value)->headline()->toString(),
            ])
            ->values()
            ->all();
    }
}
