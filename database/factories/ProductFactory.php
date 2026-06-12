<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use App\ProductStatus;
use App\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'type' => ProductType::Jar,
            'capacity_liters' => fake()->randomElement([15, 20, 25]),
            'unit_price' => fake()->randomFloat(2, 30, 120),
            'deposit_amount' => fake()->randomFloat(2, 100, 500),
            'is_returnable' => true,
            'status' => ProductStatus::Active,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Inactive,
        ]);
    }
}
