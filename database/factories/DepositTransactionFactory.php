<?php

namespace Database\Factories;

use App\DepositTransactionType;
use App\Models\CustomerDeposit;
use App\Models\DepositTransaction;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepositTransaction>
 */
class DepositTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_deposit_id' => CustomerDeposit::factory(),
            'type' => DepositTransactionType::Collect,
            'amount' => '300.00',
            'balance_after' => '300.00',
            'jar_count' => 1,
            'product_id' => null,
            'reference_type' => null,
            'reference_id' => null,
            'description' => fake()->sentence(),
            'created_by' => null,
            'created_at' => now(),
        ];
    }

    public function forDeposit(CustomerDeposit $deposit): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $deposit->tenant_id,
            'customer_deposit_id' => $deposit->id,
        ]);
    }
}
