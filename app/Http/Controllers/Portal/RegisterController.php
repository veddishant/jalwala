<?php

namespace App\Http\Controllers\Portal;

use App\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\RegisterCustomerRequest;
use App\Models\Tenant;
use App\Services\CustomerOnboardingService;
use App\Support\TenantContext;
use App\TenantStatus;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        private CustomerOnboardingService $onboardingService,
    ) {}

    public function create(Tenant $tenant): Response
    {
        abort_unless($tenant->status === TenantStatus::Active, 404);

        return Inertia::render('portal/register', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
        ]);
    }

    public function store(RegisterCustomerRequest $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($tenant->status === TenantStatus::Active, 404);

        TenantContext::setId($tenant->id);

        $customer = $this->onboardingService->onboard([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'status' => CustomerStatus::Active->value,
            'address' => $request->validated('address'),
            'portal' => [
                'create' => true,
                'password' => $request->validated('password'),
            ],
        ], $tenant->id);

        $user = $customer->user;

        if ($user === null) {
            abort(500, 'Portal account could not be created.');
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('portal.dashboard');
    }
}
