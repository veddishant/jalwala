<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateProfileRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $customer = $this->resolveCustomer($request);

        $this->authorize('view', $customer);

        $customer->load('defaultAddress');

        return Inertia::render('portal/profile', [
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'status' => $customer->status->value,
                'address' => $customer->defaultAddress ? [
                    'label' => $customer->defaultAddress->label,
                    'address_line_1' => $customer->defaultAddress->address_line_1,
                    'address_line_2' => $customer->defaultAddress->address_line_2,
                    'city' => $customer->defaultAddress->city,
                    'state' => $customer->defaultAddress->state,
                    'postal_code' => $customer->defaultAddress->postal_code,
                    'delivery_instructions' => $customer->defaultAddress->delivery_instructions,
                ] : null,
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $customer = $this->resolveCustomer($request);

        $customer->update([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
        ]);

        $request->user()?->update([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email') ?? $request->user()->email,
        ]);

        $address = $customer->defaultAddress;

        if ($address === null) {
            CustomerAddress::query()->create([
                ...$request->validated('address'),
                'customer_id' => $customer->id,
                'is_default' => true,
            ]);
        } else {
            $address->update($request->validated('address'));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated successfully.')]);

        return to_route('portal.profile.edit');
    }

    private function resolveCustomer(Request $request): Customer
    {
        TenantContext::resolveFromAuthenticatedUser($request->user());

        $customer = $request->user()?->customer;

        if ($customer === null) {
            abort(404);
        }

        return $customer;
    }
}
