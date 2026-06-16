<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\RegisterSupplierRequest;
use App\Services\TenantOnboardingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SupplierRegisterController extends Controller
{
    public function __construct(
        private TenantOnboardingService $onboardingService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('supplier/register', [
            'defaults' => [
                'timezone' => 'Asia/Kolkata',
                'currency' => 'INR',
            ],
            'timezones' => [
                ['value' => 'Asia/Kolkata', 'label' => 'India (IST)'],
                ['value' => 'Asia/Dubai', 'label' => 'UAE (GST)'],
                ['value' => 'Asia/Singapore', 'label' => 'Singapore'],
            ],
            'currencies' => [
                ['value' => 'INR', 'label' => 'INR — Indian Rupee'],
                ['value' => 'AED', 'label' => 'AED — UAE Dirham'],
                ['value' => 'SGD', 'label' => 'SGD — Singapore Dollar'],
            ],
        ]);
    }

    public function store(RegisterSupplierRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $result = $this->onboardingService->onboard([
            'business_name' => $validated['business_name'],
            'slug' => $validated['slug'] ?? null,
            'timezone' => $validated['timezone'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'admin' => [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
            ],
        ]);

        $admin = $result['admin'];

        event(new Registered($admin));

        Auth::login($admin);

        return to_route('admin.dashboard')
            ->with('status', 'Welcome! Your supplier account is ready.');
    }
}
