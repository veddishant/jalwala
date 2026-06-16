<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('impersonate', $tenant);

        $request->session()->put('active_tenant_id', $tenant->id);
        $request->session()->put('impersonating_tenant', true);

        return to_route('admin.dashboard')
            ->with('status', "Now viewing {$tenant->name} as support.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $request->session()->forget(['active_tenant_id', 'impersonating_tenant']);

        return to_route('platform.tenants.index')
            ->with('status', 'Stopped impersonating tenant.');
    }
}
