<?php

namespace App\Services;

use App\DepositTransactionType;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\DepositTransaction;
use App\Models\Product;
use App\ProductStatus;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DepositService
{
    public function ensureForCustomer(Customer $customer): CustomerDeposit
    {
        TenantContext::setId($customer->tenant_id);

        return CustomerDeposit::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'tenant_id' => $customer->tenant_id,
                'balance' => '0.00',
            ],
        );
    }

    public function collect(
        CustomerDeposit $deposit,
        Product $product,
        int $jarCount,
        int $createdBy,
        ?string $amount = null,
        ?string $description = null,
    ): DepositTransaction {
        if ($jarCount < 1) {
            throw new InvalidArgumentException('Jar count must be at least 1.');
        }

        if (! $product->is_returnable) {
            throw new InvalidArgumentException('Deposits can only be collected for returnable products.');
        }

        $totalAmount = $amount ?? bcmul((string) $jarCount, $product->deposit_amount, 2);

        if (bccomp($totalAmount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Deposit amount must be greater than zero.');
        }

        return $this->increaseBalance(
            deposit: $deposit,
            amount: $totalAmount,
            type: DepositTransactionType::Collect,
            jarCount: $jarCount,
            productId: $product->id,
            description: $description ?? "Collected deposit for {$jarCount} × {$product->name}",
            createdBy: $createdBy,
        );
    }

    public function refund(
        CustomerDeposit $deposit,
        int $jarCount,
        int $createdBy,
        ?string $amount = null,
        ?int $productId = null,
        ?string $description = null,
    ): DepositTransaction {
        if ($jarCount < 1) {
            throw new InvalidArgumentException('Jar count must be at least 1.');
        }

        $refundAmount = $amount ?? $deposit->balance;

        if (bccomp($refundAmount, $deposit->balance, 2) > 0) {
            throw new InvalidArgumentException('Refund amount cannot exceed the deposit balance.');
        }

        if (bccomp($refundAmount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Refund amount must be greater than zero.');
        }

        return $this->decreaseBalance(
            deposit: $deposit,
            amount: $refundAmount,
            type: DepositTransactionType::Refund,
            jarCount: $jarCount,
            productId: $productId,
            description: $description ?? "Refunded deposit for {$jarCount} jar(s)",
            createdBy: $createdBy,
        );
    }

    public function adjust(
        CustomerDeposit $deposit,
        string $amount,
        string $direction,
        string $reason,
        int $createdBy,
        int $jarCount = 0,
    ): DepositTransaction {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Adjustment amount must be greater than zero.');
        }

        if ($direction === 'increase') {
            return $this->increaseBalance(
                deposit: $deposit,
                amount: $amount,
                type: DepositTransactionType::Adjustment,
                jarCount: $jarCount,
                productId: null,
                description: $reason,
                createdBy: $createdBy,
            );
        }

        if (bccomp($amount, $deposit->balance, 2) > 0) {
            throw new InvalidArgumentException('Adjustment cannot reduce balance below zero.');
        }

        return $this->decreaseBalance(
            deposit: $deposit,
            amount: $amount,
            type: DepositTransactionType::Adjustment,
            jarCount: $jarCount,
            productId: null,
            description: $reason,
            createdBy: $createdBy,
        );
    }

    public function refundAll(CustomerDeposit $deposit, int $createdBy, ?string $description = null): ?DepositTransaction
    {
        if (bccomp($deposit->balance, '0', 2) <= 0) {
            return null;
        }

        $heldJars = $this->heldJarCount($deposit);

        return $this->decreaseBalance(
            deposit: $deposit,
            amount: $deposit->balance,
            type: DepositTransactionType::Refund,
            jarCount: max($heldJars, 0),
            productId: null,
            description: $description ?? 'Full deposit refund on customer closure',
            createdBy: $createdBy,
        );
    }

    public function heldJarCount(CustomerDeposit $deposit): int
    {
        $collectJars = (int) DepositTransaction::query()
            ->where('customer_deposit_id', $deposit->id)
            ->whereIn('type', [
                DepositTransactionType::Collect,
                DepositTransactionType::Adjustment,
            ])
            ->where('jar_count', '>', 0)
            ->sum('jar_count');

        $refundJars = (int) DepositTransaction::query()
            ->where('customer_deposit_id', $deposit->id)
            ->whereIn('type', [
                DepositTransactionType::Refund,
            ])
            ->sum('jar_count');

        $adjustmentDecreaseJars = (int) DepositTransaction::query()
            ->where('customer_deposit_id', $deposit->id)
            ->where('type', DepositTransactionType::Adjustment)
            ->where('jar_count', '<', 0)
            ->sum('jar_count');

        return max(0, $collectJars - $refundJars + $adjustmentDecreaseJars);
    }

    /**
     * @return list<array{product_id: int, product_name: string, jar_count: int, deposit_per_unit: string}>
     */
    public function jarSummaryByProduct(CustomerDeposit $deposit): array
    {
        $transactions = DepositTransaction::query()
            ->where('customer_deposit_id', $deposit->id)
            ->whereNotNull('product_id')
            ->with('product:id,name,deposit_amount')
            ->get();

        $summary = [];

        foreach ($transactions as $transaction) {
            $productId = (int) $transaction->product_id;
            $delta = match ($transaction->type) {
                DepositTransactionType::Collect => $transaction->jar_count,
                DepositTransactionType::Refund => -$transaction->jar_count,
                DepositTransactionType::Adjustment => $transaction->jar_count,
            };

            if (! isset($summary[$productId])) {
                $summary[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $transaction->product?->name ?? 'Unknown',
                    'jar_count' => 0,
                    'deposit_per_unit' => $transaction->product?->deposit_amount ?? '0.00',
                ];
            }

            $summary[$productId]['jar_count'] += $delta;
        }

        return collect($summary)
            ->filter(fn (array $row): bool => $row['jar_count'] > 0)
            ->values()
            ->all();
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
            ->all();
    }

    private function increaseBalance(
        CustomerDeposit $deposit,
        string $amount,
        DepositTransactionType $type,
        int $jarCount,
        ?int $productId,
        ?string $description,
        int $createdBy,
    ): DepositTransaction {
        return DB::transaction(function () use (
            $deposit,
            $amount,
            $type,
            $jarCount,
            $productId,
            $description,
            $createdBy,
        ): DepositTransaction {
            $lockedDeposit = CustomerDeposit::query()->lockForUpdate()->findOrFail($deposit->id);
            $balanceAfter = bcadd($lockedDeposit->balance, $amount, 2);

            $transaction = DepositTransaction::query()->create([
                'tenant_id' => $lockedDeposit->tenant_id,
                'customer_deposit_id' => $lockedDeposit->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'jar_count' => $jarCount,
                'product_id' => $productId,
                'description' => $description,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            $lockedDeposit->update(['balance' => $balanceAfter]);

            return $transaction;
        });
    }

    private function decreaseBalance(
        CustomerDeposit $deposit,
        string $amount,
        DepositTransactionType $type,
        int $jarCount,
        ?int $productId,
        ?string $description,
        int $createdBy,
    ): DepositTransaction {
        return DB::transaction(function () use (
            $deposit,
            $amount,
            $type,
            $jarCount,
            $productId,
            $description,
            $createdBy,
        ): DepositTransaction {
            $lockedDeposit = CustomerDeposit::query()->lockForUpdate()->findOrFail($deposit->id);

            if (bccomp($amount, $lockedDeposit->balance, 2) > 0) {
                throw new InvalidArgumentException('Insufficient deposit balance.');
            }

            $balanceAfter = bcsub($lockedDeposit->balance, $amount, 2);

            $transaction = DepositTransaction::query()->create([
                'tenant_id' => $lockedDeposit->tenant_id,
                'customer_deposit_id' => $lockedDeposit->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'jar_count' => $jarCount,
                'product_id' => $productId,
                'description' => $description,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            $lockedDeposit->update(['balance' => $balanceAfter]);

            return $transaction;
        });
    }
}
