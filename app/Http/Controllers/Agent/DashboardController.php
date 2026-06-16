<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = (int) $request->user()?->tenant_id;
        $tenant = Tenant::query()->findOrFail($tenantId);

        return Inertia::render('agent/dashboard', [
            'tenant' => [
                'name' => $tenant->name,
            ],
            'stats' => $this->dashboardService->agentStats($tenantId),
            'todayDeliveries' => $this->dashboardService->agentTodayDeliveries($tenantId),
        ]);
    }
}
