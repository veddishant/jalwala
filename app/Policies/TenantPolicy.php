<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\TenantStatus;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('platform.tenants.view');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->can('platform.tenants.view');
    }

    public function create(User $user): bool
    {
        return $user->can('platform.tenants.create');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->can('platform.tenants.update');
    }

    public function suspend(User $user, Tenant $tenant): bool
    {
        return $user->can('platform.tenants.suspend');
    }

    public function impersonate(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin()
            && $user->can('platform.tenants.view')
            && $tenant->status === TenantStatus::Active;
    }
}
