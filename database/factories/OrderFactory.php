<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Tenant;
use App\OrderSource;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'uuid' => (string) Str::uuid(),
            'customer_id' => Customer::factory(),
            'customer_address_id' => CustomerAddress::factory(),
            'subscription_id' => null,
            'source' => OrderSource::Manual,
            'status' => OrderStatus::Draft,
            'subtotal' => '50.00',
            'total' => '50.00',
            'wallet_amount_charged' => '0.00',
            'scheduled_date' => now()->addDay()->toDateString(),
            'delivered_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Pending,
        ]);
    }
}
