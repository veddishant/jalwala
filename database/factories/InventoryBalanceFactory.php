<?php

namespace Database\Factories;

use App\Models\InventoryBalance;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'inventory_location_id' => InventoryLocation::factory(),
            'product_id' => Product::factory(),
            'filled_quantity' => 0,
            'empty_quantity' => 0,
            'updated_at' => now(),
        ];
    }

    public function forLocation(InventoryLocation $location): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $location->tenant_id,
            'inventory_location_id' => $location->id,
        ]);
    }
}
