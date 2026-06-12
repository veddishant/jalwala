<?php

namespace App\Policies;

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\User;
use App\Support\TenantContext;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        if (! $user->can('customers.view')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $customer->user_id === $user->id;
        }

        return $this->sameTenant($user, $customer);
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create') && $this->hasTenantContext($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($customer->isClosed()) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $customer->user_id === $user->id;
        }

        return $user->can('customers.update') && $this->sameTenant($user, $customer);
    }

    public function pause(User $user, Customer $customer): bool
    {
        return $user->can('customers.pause')
            && $this->sameTenant($user, $customer)
            && $customer->status === CustomerStatus::Active;
    }

    public function resume(User $user, Customer $customer): bool
    {
        return $user->can('customers.pause')
            && $this->sameTenant($user, $customer)
            && $customer->status === CustomerStatus::Paused;
    }

    public function close(User $user, Customer $customer): bool
    {
        return $user->can('customers.close')
            && $this->sameTenant($user, $customer)
            && ! $customer->isClosed();
    }

    public function manageAddresses(User $user, Customer $customer): bool
    {
        if (! $user->can('customers.addresses.manage') || $customer->isClosed()) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $customer->user_id === $user->id;
        }

        return $this->sameTenant($user, $customer);
    }

    private function sameTenant(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin() && TenantContext::getId() !== null) {
            return $customer->tenant_id === TenantContext::getId();
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $customer->tenant_id === $user->tenant_id;
    }

    private function hasTenantContext(User $user): bool
    {
        if ($user->tenant_id !== null) {
            return true;
        }

        return $user->isSuperAdmin() && TenantContext::getId() !== null;
    }
}
