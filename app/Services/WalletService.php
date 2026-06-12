<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Notifications\LowWalletBalanceNotification;
use App\Support\TenantContext;
use App\WalletTransactionCategory;
use App\WalletTransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function ensureForCustomer(
        Customer $customer,
        ?string $openingBalance = null,
        ?string $lowBalanceThreshold = null,
        ?int $createdBy = null,
    ): Wallet {
        return DB::transaction(function () use ($customer, $openingBalance, $lowBalanceThreshold, $createdBy): Wallet {
            TenantContext::setId($customer->tenant_id);

            $wallet = Wallet::query()->firstOrCreate(
                ['customer_id' => $customer->id],
                [
                    'tenant_id' => $customer->tenant_id,
                    'balance' => '0.00',
                    'low_balance_threshold' => $lowBalanceThreshold,
                ],
            );

            if ($lowBalanceThreshold !== null) {
                $wallet->update(['low_balance_threshold' => $lowBalanceThreshold]);
            }

            if ($openingBalance !== null && bccomp($openingBalance, '0', 2) > 0) {
                $this->credit(
                    wallet: $wallet,
                    amount: $openingBalance,
                    category: WalletTransactionCategory::OpeningBalance,
                    idempotencyKey: "opening-balance-{$wallet->id}",
                    description: 'Opening balance',
                    createdBy: $createdBy,
                );
            }

            return $wallet->fresh();
        });
    }

    public function topUp(
        Wallet $wallet,
        string $amount,
        ?string $description,
        int $createdBy,
    ): WalletTransaction {
        return $this->credit(
            wallet: $wallet,
            amount: $amount,
            category: WalletTransactionCategory::TopUp,
            idempotencyKey: 'top-up-'.Str::uuid()->toString(),
            description: $description ?? 'Wallet top-up',
            createdBy: $createdBy,
        );
    }

    public function adjust(
        Wallet $wallet,
        string $amount,
        string $direction,
        string $reason,
        int $createdBy,
    ): WalletTransaction {
        if ($direction === WalletTransactionType::Credit->value) {
            return $this->credit(
                wallet: $wallet,
                amount: $amount,
                category: WalletTransactionCategory::Adjustment,
                idempotencyKey: 'adjustment-'.Str::uuid()->toString(),
                description: $reason,
                createdBy: $createdBy,
            );
        }

        return $this->debit(
            wallet: $wallet,
            amount: $amount,
            category: WalletTransactionCategory::Adjustment,
            idempotencyKey: 'adjustment-'.Str::uuid()->toString(),
            description: $reason,
            createdBy: $createdBy,
        );
    }

    public function credit(
        Wallet $wallet,
        string $amount,
        WalletTransactionCategory $category,
        string $idempotencyKey,
        ?string $description = null,
        ?int $createdBy = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransaction {
        return $this->recordTransaction(
            wallet: $wallet,
            type: WalletTransactionType::Credit,
            amount: $amount,
            category: $category,
            idempotencyKey: $idempotencyKey,
            description: $description,
            createdBy: $createdBy,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );
    }

    public function debit(
        Wallet $wallet,
        string $amount,
        WalletTransactionCategory $category,
        string $idempotencyKey,
        ?string $description = null,
        ?int $createdBy = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransaction {
        return $this->recordTransaction(
            wallet: $wallet,
            type: WalletTransactionType::Debit,
            amount: $amount,
            category: $category,
            idempotencyKey: $idempotencyKey,
            description: $description,
            createdBy: $createdBy,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );
    }

    private function recordTransaction(
        Wallet $wallet,
        WalletTransactionType $type,
        string $amount,
        WalletTransactionCategory $category,
        string $idempotencyKey,
        ?string $description,
        ?int $createdBy,
        ?string $referenceType,
        ?int $referenceId,
    ): WalletTransaction {
        return DB::transaction(function () use (
            $wallet,
            $type,
            $amount,
            $category,
            $idempotencyKey,
            $description,
            $createdBy,
            $referenceType,
            $referenceId,
        ): WalletTransaction {
            $existing = WalletTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $lockedWallet = Wallet::query()->lockForUpdate()->findOrFail($wallet->id);

            $balanceAfter = $type === WalletTransactionType::Credit
                ? bcadd($lockedWallet->balance, $amount, 2)
                : bcsub($lockedWallet->balance, $amount, 2);

            $transaction = WalletTransaction::query()->create([
                'tenant_id' => $lockedWallet->tenant_id,
                'wallet_id' => $lockedWallet->id,
                'type' => $type,
                'category' => $category,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            $lockedWallet->update(['balance' => $balanceAfter]);

            $this->notifyIfBelowThreshold($lockedWallet->fresh());

            return $transaction;
        });
    }

    private function notifyIfBelowThreshold(Wallet $wallet): void
    {
        if (! $wallet->isBelowThreshold()) {
            return;
        }

        $wallet->loadMissing('customer.user');

        $notifiable = $wallet->customer?->user;

        if ($notifiable instanceof User) {
            $notifiable->notify(new LowWalletBalanceNotification($wallet));
        }
    }
}
