<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use App\TenantStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsSet
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->tenant_id !== null) {
            TenantContext::setId($user->tenant_id);

            return $next($request);
        }

        if ($user->hasRole('super-admin')) {
            $activeTenantId = $this->activeTenantIdFromSession($request);

            if ($this->requiresTenant($request)) {
                if ($activeTenantId === null) {
                    $activeTenantId = $this->resolveSingleActiveTenantId();
                }

                if ($activeTenantId === null) {
                    abort(403, 'Please select a tenant to continue.');
                }

                TenantContext::setId((int) $activeTenantId);
            } elseif ($activeTenantId !== null) {
                TenantContext::setId((int) $activeTenantId);
            } else {
                TenantContext::bypass();
            }

            return $next($request);
        }

        if ($this->requiresTenant($request)) {
            abort(403, 'Tenant context is required.');
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        TenantContext::clear();
    }

    private function requiresTenant(Request $request): bool
    {
        $path = $request->path();

        return str_starts_with($path, 'admin/')
            || str_starts_with($path, 'agent/')
            || str_starts_with($path, 'portal/');
    }

    private function activeTenantIdFromSession(Request $request): mixed
    {
        if ($request->hasSession()) {
            try {
                $tenantId = $request->session()->get('active_tenant_id');

                if ($tenantId !== null) {
                    return $tenantId;
                }
            } catch (\RuntimeException) {
                //
            }
        }

        if (app()->bound('session')) {
            return app('session')->get('active_tenant_id');
        }

        return null;
    }

    private function resolveSingleActiveTenantId(): ?int
    {
        $activeTenantIds = Tenant::query()
            ->where('status', TenantStatus::Active)
            ->orderBy('id')
            ->pluck('id');

        if ($activeTenantIds->count() !== 1) {
            return null;
        }

        return (int) $activeTenantIds->first();
    }
}
