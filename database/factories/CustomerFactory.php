<?php

namespace Database\Factories;

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'code' => 'CUST-'.fake()->unique()->numerify('####'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('##########'),
            'email' => fake()->optional()->safeEmail(),
            'status' => CustomerStatus::Active,
            'closed_at' => null,
            'closure_reason' => null,
            'notes' => null,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function prospect(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerStatus::Prospect,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerStatus::Paused,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerStatus::Closed,
            'closed_at' => now(),
            'closure_reason' => 'Test closure',
        ]);
    }
}
