<?php

namespace Database\Factories;

use App\InventoryMovementType;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
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
            'movement_type' => InventoryMovementType::FilledIn,
            'quantity' => 1,
            'reference_type' => 'manual',
            'reference_id' => null,
            'notes' => null,
            'created_by' => null,
            'created_at' => now(),
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
