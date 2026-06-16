<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        if ($user->hasRole('supplier-admin')) {
            return redirect()->route('admin.customers.index');
        }

        if ($user->hasRole('customer')) {
            return redirect()->route('portal.dashboard');
        }

        if ($user->hasRole('delivery-agent')) {
            return redirect()->route('agent.dashboard');
        }

        return Inertia::render('dashboard');
    }
}
