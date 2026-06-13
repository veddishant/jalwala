<?php

namespace App\Services;

use App\InventoryLocationType;
use App\InventoryMovementType;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\ProductStatus;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function ensureWarehouseLocation(Tenant $tenant): InventoryLocation
    {
        TenantContext::setId($tenant->id);

        return InventoryLocation::query()->firstOrCreate(
            [
                'locatable_type' => InventoryLocationType::TenantWarehouse,
                'locatable_id' => $tenant->id,
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Warehouse',
            ],
        );
    }

    public function ensureCustomerLocation(Customer $customer): InventoryLocation
    {
        TenantContext::setId($customer->tenant_id);

        return InventoryLocation::query()->firstOrCreate(
            [
                'locatable_type' => InventoryLocationType::Customer,
                'locatable_id' => $customer->id,
            ],
            [
                'tenant_id' => $customer->tenant_id,
                'name' => $customer->name.' premises',
            ],
        );
    }

    public function ensureBalance(InventoryLocation $location, Product $product): InventoryBalance
    {
        TenantContext::setId($location->tenant_id);

        return InventoryBalance::query()->firstOrCreate(
            [
                'inventory_location_id' => $location->id,
                'product_id' => $product->id,
            ],
            [
                'tenant_id' => $location->tenant_id,
                'filled_quantity' => 0,
                'empty_quantity' => 0,
                'updated_at' => now(),
            ],
        );
    }

    public function receiveStock(
        InventoryLocation $location,
        Product $product,
        int $quantity,
        int $createdBy,
        ?string $notes = null,
    ): InventoryMovement {
        if (! $location->isWarehouse()) {
            throw new InvalidArgumentException('Stock can only be received at the warehouse.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        if (! $product->is_returnable) {
            throw new InvalidArgumentException('Only returnable products can be tracked in inventory.');
        }

        return DB::transaction(function () use ($location, $product, $quantity, $createdBy, $notes): InventoryMovement {
            $balance = $this->ensureBalance($location, $product);

            return $this->recordMovement(
                balance: $balance,
                movementType: InventoryMovementType::FilledIn,
                quantity: $quantity,
                referenceType: 'manual',
                referenceId: null,
                createdBy: $createdBy,
                notes: $notes ?? "Received {$quantity} filled jar(s)",
            );
        });
    }

    public function adjust(
        InventoryLocation $location,
        Product $product,
        string $jarType,
        string $direction,
        int $quantity,
        string $reason,
        int $createdBy,
    ): InventoryMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        if (! in_array($jarType, ['filled', 'empty'], true)) {
            throw new InvalidArgumentException('Jar type must be filled or empty.');
        }

        if (! in_array($direction, ['increase', 'decrease'], true)) {
            throw new InvalidArgumentException('Direction must be increase or decrease.');
        }

        if (! $product->is_returnable) {
            throw new InvalidArgumentException('Only returnable products can be tracked in inventory.');
        }

        $movementType = match ([$jarType, $direction]) {
            ['filled', 'increase'] => InventoryMovementType::FilledIn,
            ['filled', 'decrease'] => InventoryMovementType::FilledOut,
            ['empty', 'increase'] => InventoryMovementType::EmptyIn,
            ['empty', 'decrease'] => InventoryMovementType::EmptyOut,
        };

        return DB::transaction(function () use (
            $location,
            $product,
            $movementType,
            $quantity,
            $reason,
            $createdBy,
        ): InventoryMovement {
            $balance = $this->ensureBalance($location, $product);

            return $this->recordMovement(
                balance: $balance,
                movementType: $movementType,
                quantity: $quantity,
                referenceType: 'manual',
                referenceId: null,
                createdBy: $createdBy,
                notes: $reason,
            );
        });
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $emptiesCollected
     */
    public function transferForDelivery(
        Order $order,
        array $emptiesCollected = [],
        ?int $createdBy = null,
    ): void {
        if ($this->hasDeliveryMovements($order)) {
            return;
        }

        $order->loadMissing(['items.product', 'customer']);

        DB::transaction(function () use ($order, $emptiesCollected, $createdBy): void {
            $tenant = Tenant::query()->findOrFail($order->tenant_id);
            $warehouse = $this->ensureWarehouseLocation($tenant);
            $customerLocation = $this->ensureCustomerLocation($order->customer);

            foreach ($order->items as $item) {
                $product = $item->product;

                if ($product === null || ! $product->is_returnable) {
                    continue;
                }

                $quantity = (int) $item->quantity;

                if ($quantity < 1) {
                    continue;
                }

                $warehouseBalance = $this->ensureBalance($warehouse, $product);
                $customerBalance = $this->ensureBalance($customerLocation, $product);

                $this->recordMovement(
                    balance: $warehouseBalance,
                    movementType: InventoryMovementType::FilledOut,
                    quantity: $quantity,
                    referenceType: 'order',
                    referenceId: $order->id,
                    createdBy: $createdBy,
                    notes: "Delivered to {$order->customer->name}",
                );

                $this->recordMovement(
                    balance: $customerBalance,
                    movementType: InventoryMovementType::FilledIn,
                    quantity: $quantity,
                    referenceType: 'order',
                    referenceId: $order->id,
                    createdBy: $createdBy,
                    notes: "Received from delivery order {$order->uuid}",
                );
            }

            foreach ($emptiesCollected as $empty) {
                $product = Product::query()
                    ->where('tenant_id', $order->tenant_id)
                    ->where('id', $empty['product_id'])
                    ->where('is_returnable', true)
                    ->first();

                if ($product === null) {
                    continue;
                }

                $quantity = (int) $empty['quantity'];

                if ($quantity < 1) {
                    continue;
                }

                $warehouseBalance = $this->ensureBalance($warehouse, $product);
                $customerBalance = $this->ensureBalance($customerLocation, $product);

                $this->recordMovement(
                    balance: $customerBalance,
                    movementType: InventoryMovementType::EmptyOut,
                    quantity: $quantity,
                    referenceType: 'order',
                    referenceId: $order->id,
                    createdBy: $createdBy,
                    notes: "Empties collected on order {$order->uuid}",
                );

                $this->recordMovement(
                    balance: $warehouseBalance,
                    movementType: InventoryMovementType::EmptyIn,
                    quantity: $quantity,
                    referenceType: 'order',
                    referenceId: $order->id,
                    createdBy: $createdBy,
                    notes: "Empties collected from {$order->customer->name}",
                );
            }
        });
    }

    public function settleOnCustomerClosure(Customer $customer, int $createdBy): void
    {
        TenantContext::setId($customer->tenant_id);

        DB::transaction(function () use ($customer, $createdBy): void {
            $tenant = Tenant::query()->findOrFail($customer->tenant_id);
            $warehouse = $this->ensureWarehouseLocation($tenant);
            $customerLocation = $this->ensureCustomerLocation($customer);

            $balances = InventoryBalance::query()
                ->where('inventory_location_id', $customerLocation->id)
                ->lockForUpdate()
                ->get();

            foreach ($balances as $balance) {
                $totalJars = $balance->filled_quantity + $balance->empty_quantity;

                if ($totalJars < 1) {
                    continue;
                }

                $product = Product::query()->findOrFail($balance->product_id);
                $warehouseBalance = $this->ensureBalance($warehouse, $product);

                if ($balance->filled_quantity > 0) {
                    $this->recordMovement(
                        balance: $balance,
                        movementType: InventoryMovementType::FilledOut,
                        quantity: $balance->filled_quantity,
                        referenceType: 'manual',
                        referenceId: null,
                        createdBy: $createdBy,
                        notes: 'Customer closure — filled jars collected',
                    );
                }

                if ($balance->empty_quantity > 0) {
                    $this->recordMovement(
                        balance: $balance,
                        movementType: InventoryMovementType::EmptyOut,
                        quantity: $balance->empty_quantity,
                        referenceType: 'manual',
                        referenceId: null,
                        createdBy: $createdBy,
                        notes: 'Customer closure — empty jars collected',
                    );
                }

                $this->recordMovement(
                    balance: $warehouseBalance,
                    movementType: InventoryMovementType::EmptyIn,
                    quantity: $totalJars,
                    referenceType: 'manual',
                    referenceId: null,
                    createdBy: $createdBy,
                    notes: "Customer closure — jars collected from {$customer->name}",
                );
            }
        });
    }

    /**
     * @return Collection<int, InventoryBalance>
     */
    public function warehouseBalances(Tenant $tenant): Collection
    {
        $warehouse = $this->ensureWarehouseLocation($tenant);

        return InventoryBalance::query()
            ->where('inventory_location_id', $warehouse->id)
            ->with('product:id,name,sku,capacity_liters')
            ->orderBy('product_id')
            ->get();
    }

    /**
     * @return list<array{id: int, name: string, sku: string, deposit_amount: string, capacity_liters: string|null}>
     */
    public function returnableProductsForTenant(int $tenantId): array
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_returnable', true)
            ->where('status', ProductStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'deposit_amount', 'capacity_liters'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'deposit_amount' => $product->deposit_amount,
                'capacity_liters' => $product->capacity_liters,
            ])
            ->values()
            ->all();
    }

    public function hasDeliveryMovements(Order $order): bool
    {
        return InventoryMovement::query()
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->exists();
    }

    private function recordMovement(
        InventoryBalance $balance,
        InventoryMovementType $movementType,
        int $quantity,
        string $referenceType,
        ?int $referenceId,
        ?int $createdBy,
        ?string $notes,
    ): InventoryMovement {
        $lockedBalance = InventoryBalance::query()
            ->lockForUpdate()
            ->findOrFail($balance->id);

        $this->applyQuantityChange($lockedBalance, $movementType, $quantity);

        $lockedBalance->updated_at = now();
        $lockedBalance->save();

        return InventoryMovement::query()->create([
            'tenant_id' => $lockedBalance->tenant_id,
            'inventory_location_id' => $lockedBalance->inventory_location_id,
            'product_id' => $lockedBalance->product_id,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $createdBy,
            'created_at' => now(),
        ]);
    }

    private function applyQuantityChange(
        InventoryBalance $balance,
        InventoryMovementType $movementType,
        int $quantity,
    ): void {
        match ($movementType) {
            InventoryMovementType::FilledIn => $balance->filled_quantity += $quantity,
            InventoryMovementType::FilledOut => $this->decreaseQuantity(
                $balance,
                'filled_quantity',
                $quantity,
            ),
            InventoryMovementType::EmptyIn => $balance->empty_quantity += $quantity,
            InventoryMovementType::EmptyOut => $this->decreaseQuantity(
                $balance,
                'empty_quantity',
                $quantity,
            ),
            InventoryMovementType::Adjustment => throw new InvalidArgumentException('Use specific movement types for adjustments.'),
        };
    }

    private function decreaseQuantity(InventoryBalance $balance, string $field, int $quantity): void
    {
        if ($balance->{$field} < $quantity) {
            throw new InvalidArgumentException('Insufficient inventory quantity for this movement.');
        }

        $balance->{$field} -= $quantity;
    }
}
