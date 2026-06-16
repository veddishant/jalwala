<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\TenantStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Tenant::class);

        $tenantCounts = Tenant::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return Inertia::render('platform/dashboard', [
            'stats' => [
                'total' => Tenant::query()->count(),
                'active' => (int) ($tenantCounts[TenantStatus::Active->value] ?? 0),
                'suspended' => (int) ($tenantCounts[TenantStatus::Suspended->value] ?? 0),
                'closed' => (int) ($tenantCounts[TenantStatus::Closed->value] ?? 0),
            ],
            'recentTenants' => Tenant::query()
                ->with('subscription')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Tenant $tenant): array => $this->tenantSummary($tenant)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantSummary(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'status' => $tenant->status->value,
            'status_label' => $tenant->status->label(),
            'plan' => $tenant->subscription?->plan,
            'subscription_status' => $tenant->subscription?->status?->value,
            'created_at' => $tenant->created_at?->toIso8601String(),
        ];
    }
}
