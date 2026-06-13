<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerInventoryService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @return list<array{
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     filled_quantity: int,
     *     empty_quantity: int,
     *     total_jars: int
     * }>
     */
    public function summaryForCustomer(Customer $customer): array
    {
        TenantContext::setId($customer->tenant_id);

        $location = $this->inventoryService->ensureCustomerLocation($customer);

        return InventoryBalance::query()
            ->where('inventory_location_id', $location->id)
            ->with('product:id,name,sku')
            ->orderBy('product_id')
            ->get()
            ->map(fn (InventoryBalance $balance): array => [
                'product_id' => $balance->product_id,
                'product_name' => $balance->product->name,
                'sku' => $balance->product->sku,
                'filled_quantity' => $balance->filled_quantity,
                'empty_quantity' => $balance->empty_quantity,
                'total_jars' => $balance->filled_quantity + $balance->empty_quantity,
            ])
            ->filter(fn (array $row): bool => $row['total_jars'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return LengthAwarePaginator<int, InventoryMovement>
     */
    public function movementsForCustomer(Customer $customer, int $perPage = 15): LengthAwarePaginator
    {
        TenantContext::setId($customer->tenant_id);

        $location = $this->inventoryService->ensureCustomerLocation($customer);

        return InventoryMovement::query()
            ->where('inventory_location_id', $location->id)
            ->with(['product:id,name', 'createdBy:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
