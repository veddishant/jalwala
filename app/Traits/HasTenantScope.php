<?php

namespace App\Traits;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait HasTenantScope
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCurrentTenant(Builder $query): Builder
    {
        if (TenantContext::isBypassed()) {
            return $query;
        }

        $tenantId = TenantContext::getId();

        if ($tenantId === null) {
            return $query;
        }

        return $query->where($query->qualifyColumn('tenant_id'), $tenantId);
    }
}
