<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\Tenant;
use App\OrderSource;
use App\ReportType;
use App\Repositories\ReportRepository;
use App\Services\ReportService;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private ReportRepository $reportRepository,
    ) {}

    private function ensureTenantContext(Request $request): void
    {
        TenantContext::resolveFromAuthenticatedUser($request->user());

        if (TenantContext::getId() !== null || TenantContext::isBypassed()) {
            return;
        }

        $user = $request->user();

        if ($user?->hasRole('super-admin') && $request->hasSession()) {
            try {
                $activeTenantId = $request->session()->get('active_tenant_id');

                if ($activeTenantId !== null) {
                    TenantContext::setId((int) $activeTenantId);

                    return;
                }
            } catch (\RuntimeException) {
                //
            }
        }

        if ($user?->hasRole('super-admin') && TenantContext::getId() === null) {
            $activeTenantCount = Tenant::query()
                ->where('status', TenantStatus::Active)
                ->count();

            if ($activeTenantCount === 1) {
                $tenantId = Tenant::query()
                    ->where('status', TenantStatus::Active)
                    ->orderBy('id')
                    ->value('id');

                if ($tenantId !== null) {
                    TenantContext::setId((int) $tenantId);
                }
            }
        }
    }

    private function currentTenant(Request $request): Tenant
    {
        $this->ensureTenantContext($request);

        $tenantId = TenantContext::getId() ?? $request->user()?->tenant_id;

        if ($tenantId === null) {
            abort(403, 'Tenant context is required.');
        }

        return Tenant::query()->findOrFail($tenantId);
    }

    private function resolveType(string $type): ReportType
    {
        return ReportType::tryFrom($type) ?? abort(404);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ReportType::class);

        $reports = collect(ReportType::all())
            ->filter(fn (ReportType $type): bool => $request->user()?->can($type->permission()) ?? false)
            ->map(fn (ReportType $type): array => [
                'type' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/reports/index', [
            'reports' => $reports,
        ]);
    }

    public function show(ReportFilterRequest $request, string $type): Response
    {
        $reportType = $this->resolveType($type);

        $this->authorize('view', $reportType);

        $tenant = $this->currentTenant($request);
        $filters = $request->filters();

        return Inertia::render('admin/reports/show', [
            'reportType' => [
                'type' => $reportType->value,
                'label' => $reportType->label(),
                'description' => $reportType->description(),
            ],
            'filters' => $filters,
            'filterOptions' => [
                'customers' => $this->reportRepository->customersForTenant($tenant->id),
                'products' => $this->reportRepository->productsForTenant($tenant->id),
                'agents' => $this->reportRepository->agentsForTenant($tenant->id),
                'sources' => collect(OrderSource::cases())->map(fn (OrderSource $source): array => [
                    'value' => $source->value,
                    'label' => $source->label(),
                ])->values()->all(),
                'grains' => [
                    ['value' => 'daily', 'label' => 'Daily'],
                    ['value' => 'weekly', 'label' => 'Weekly'],
                    ['value' => 'monthly', 'label' => 'Monthly'],
                ],
            ],
            'can' => [
                'export' => $request->user()?->can('export', $reportType) ?? false,
            ],
            'report' => Inertia::defer(fn (): array => $this->reportService->generate(
                $reportType,
                $tenant->id,
                $filters,
            )),
        ]);
    }

    public function export(ReportFilterRequest $request, string $type): StreamedResponse
    {
        $reportType = $this->resolveType($type);

        $this->authorize('export', $reportType);

        $tenant = $this->currentTenant($request);

        return $this->reportService->exportCsv(
            $reportType,
            $tenant->id,
            $request->filters(),
        );
    }
}
