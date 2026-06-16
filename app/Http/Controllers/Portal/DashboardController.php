<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
        $customer = $this->resolveCustomer($request);

        return Inertia::render('portal/dashboard', [
            'summary' => $this->dashboardService->portalStats($customer),
            'recentOrders' => $this->dashboardService->portalRecentOrders($customer),
        ]);
    }

    private function resolveCustomer(Request $request): Customer
    {
        $user = $request->user();

        $customer = Customer::query()
            ->where('user_id', $user?->id)
            ->first();

        if ($customer === null) {
            abort(404, 'Customer profile not found.');
        }

        return $customer;
    }
}
