<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Tenant;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'order_id' => Order::factory(),
            'from_status' => null,
            'to_status' => OrderStatus::Draft,
            'changed_by' => null,
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
        ]);
    }
}
