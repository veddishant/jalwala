<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;
use App\Support\TenantContext;

class WalletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('wallet.view');
    }

    public function view(User $user, Wallet $wallet): bool
    {
        if (! $user->can('wallet.view')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $wallet->customer?->user_id === $user->id;
        }

        return $this->sameTenant($user, $wallet);
    }

    public function viewLedger(User $user, Wallet $wallet): bool
    {
        if (! $user->can('wallet.view-ledger')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $wallet->customer?->user_id === $user->id;
        }

        return $this->sameTenant($user, $wallet);
    }

    public function topUp(User $user, Wallet $wallet): bool
    {
        return $user->can('wallet.top-up') && $this->sameTenant($user, $wallet);
    }

    public function adjust(User $user, Wallet $wallet): bool
    {
        return $user->can('wallet.adjust') && $this->sameTenant($user, $wallet);
    }

    public function updateThreshold(User $user, Wallet $wallet): bool
    {
        return $user->can('wallet.adjust') && $this->sameTenant($user, $wallet);
    }

    private function sameTenant(User $user, Wallet $wallet): bool
    {
        if ($user->isSuperAdmin() && TenantContext::getId() !== null) {
            return $wallet->tenant_id === TenantContext::getId();
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $wallet->tenant_id === $user->tenant_id;
    }
}
