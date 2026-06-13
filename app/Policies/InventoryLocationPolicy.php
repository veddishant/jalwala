<?php

namespace App\Policies;

use App\Models\InventoryLocation;
use App\Models\User;
use App\Support\TenantContext;

class InventoryLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, InventoryLocation $inventoryLocation): bool
    {
        if ($user->can('inventory.view')) {
            return $this->sameTenant($user, $inventoryLocation);
        }

        if ($user->can('inventory.view-customer') && $inventoryLocation->isCustomerLocation()) {
            return $this->sameTenant($user, $inventoryLocation);
        }

        return false;
    }

    public function adjust(User $user, InventoryLocation $inventoryLocation): bool
    {
        return $user->can('inventory.adjust')
            && $this->sameTenant($user, $inventoryLocation);
    }

    public function receiveStock(User $user, InventoryLocation $inventoryLocation): bool
    {
        return $user->can('inventory.adjust')
            && $inventoryLocation->isWarehouse()
            && $this->sameTenant($user, $inventoryLocation);
    }

    private function sameTenant(User $user, InventoryLocation $inventoryLocation): bool
    {
        if ($user->isSuperAdmin() && TenantContext::getId() !== null) {
            return $inventoryLocation->tenant_id === TenantContext::getId();
        }

        if ($user->tenant_id === null) {
            return false;
        }

        return $inventoryLocation->tenant_id === $user->tenant_id;
    }
}
