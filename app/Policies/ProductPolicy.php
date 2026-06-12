<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\TenantContext;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('products.view') && $this->sameTenant($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->can('products.create') && $this->hasTenantContext($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update') && $this->sameTenant($user, $product);
    }

    public function deactivate(User $user, Product $product): bool
    {
        return $user->can('products.deactivate') && $this->sameTenant($user, $product);
    }

    private function sameTenant(User $user, Product $product): bool
    {
        if ($user->isSuperAdmin() && TenantContext::getId() !== null) {
            return $product->tenant_id === TenantContext::getId();
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $product->tenant_id === $user->tenant_id;
    }

    private function hasTenantContext(User $user): bool
    {
        if ($user->tenant_id !== null) {
            return true;
        }

        return $user->isSuperAdmin() && TenantContext::getId() !== null;
    }
}
