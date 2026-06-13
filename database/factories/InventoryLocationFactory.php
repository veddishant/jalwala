<?php

namespace Database\Factories;

use App\InventoryLocationType;
use App\Models\Customer;
use App\Models\InventoryLocation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLocation>
 */
class InventoryLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'locatable_type' => InventoryLocationType::TenantWarehouse,
            'locatable_id' => fn (array $attributes) => $attributes['tenant_id'],
            'name' => 'Warehouse',
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'locatable_type' => InventoryLocationType::TenantWarehouse,
            'locatable_id' => $tenant->id,
            'name' => 'Warehouse',
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $customer->tenant_id,
            'locatable_type' => InventoryLocationType::Customer,
            'locatable_id' => $customer->id,
            'name' => $customer->name.' premises',
        ]);
    }
}
