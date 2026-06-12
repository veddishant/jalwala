<?php

namespace App\Policies;

use App\Models\User;
use App\Support\TenantContext;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view') && $this->sameTenant($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('users.create') && $this->hasTenantContext($user);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update') && $this->sameTenant($user, $model);
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->can('users.assign-roles') && $this->sameTenant($user, $model);
    }

    private function sameTenant(User $user, User $model): bool
    {
        if ($user->isSuperAdmin() && TenantContext::getId() !== null) {
            return $model->tenant_id === TenantContext::getId();
        }

        if ($user->tenant_id === null) {
            return $model->tenant_id === null;
        }

        return $model->tenant_id === $user->tenant_id;
    }

    private function hasTenantContext(User $user): bool
    {
        if ($user->tenant_id !== null) {
            return true;
        }

        return $user->isSuperAdmin() && TenantContext::getId() !== null;
    }
}
