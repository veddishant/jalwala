<?php

namespace App\Http\Requests\Portal;

use App\Models\Customer;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        $customer = $this->user()?->customer;

        return $customer instanceof Customer
            && $this->user()?->can('update', $customer);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customer = $this->user()?->customer;
        $tenantId = TenantContext::getId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where('tenant_id', $tenantId)
                    ->ignore($customer?->id),
            ],
            'address.label' => ['required', 'string', 'max:50'],
            'address.address_line_1' => ['required', 'string', 'max:255'],
            'address.address_line_2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.state' => ['required', 'string', 'max:100'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.delivery_instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
