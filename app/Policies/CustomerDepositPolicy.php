<?php

namespace App\Policies;

use App\Models\CustomerDeposit;
use App\Models\User;
use App\Support\TenantContext;

class CustomerDepositPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('deposits.view');
    }

    public function view(User $user, CustomerDeposit $customerDeposit): bool
    {
        if (! $user->can('deposits.view')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $customerDeposit->customer?->user_id === $user->id;
        }

        return $this->sameTenant($user, $customerDeposit);
    }

    public function viewLedger(User $user, CustomerDeposit $customerDeposit): bool
    {
        if (! $user->can('deposits.view-ledger')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $customerDeposit->customer?->user_id === $user->id;
        }

        return $this->sameTenant($user, $customerDeposit);
    }

    public function collect(User $user, CustomerDeposit $customerDeposit): bool
    {
        return $user->can('deposits.collect') && $this->sameTenant($user, $customerDeposit);
    }

    public function refund(User $user, CustomerDeposit $customerDeposit): bool
    {
        return $user->can('deposits.refund') && $this->sameTenant($user, $customerDeposit);
    }

    public function adjust(User $user, CustomerDeposit $customerDeposit): bool
    {
        return $user->can('deposits.adjust') && $this->sameTenant($user, $customerDeposit);
    }

    private function sameTenant(User $user, CustomerDeposit $customerDeposit): bool
    {
        if ($user->isSuperAdmin() && TenantContext::getId() !== null) {
            return $customerDeposit->tenant_id === TenantContext::getId();
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $customerDeposit->tenant_id === $user->tenant_id;
    }
}
