<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\WalletTransactionCategory;
use App\WalletTransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => fn (array $attributes) => Wallet::query()->find($attributes['wallet_id'])?->tenant_id,
            'wallet_id' => Wallet::factory(),
            'type' => WalletTransactionType::Credit,
            'category' => WalletTransactionCategory::TopUp,
            'amount' => fake()->randomFloat(2, 50, 500),
            'balance_after' => fake()->randomFloat(2, 50, 500),
            'reference_type' => null,
            'reference_id' => null,
            'idempotency_key' => Str::uuid()->toString(),
            'description' => fake()->optional()->sentence(),
            'created_by' => null,
            'created_at' => now(),
        ];
    }

    public function forWallet(Wallet $wallet): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $wallet->tenant_id,
            'wallet_id' => $wallet->id,
        ]);
    }
}
