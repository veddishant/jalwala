<?php

namespace App\Http\Requests\Admin;

use App\CustomerStatus;
use App\Models\Customer;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateCustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('customers.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::getId();
        $customerId = (int) $this->route('managedCustomer');
        $customer = Customer::query()->findOrFail($customerId);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where('tenant_id', $tenantId)
                    ->ignore($customer->id),
            ],
            'status' => ['required', 'string', Rule::enum(CustomerStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->user()?->can('manageAddresses', $customer)) {
            $rules += [
                'address.label' => ['required', 'string', 'max:50'],
                'address.address_line_1' => ['required', 'string', 'max:255'],
                'address.address_line_2' => ['nullable', 'string', 'max:255'],
                'address.city' => ['required', 'string', 'max:100'],
                'address.state' => ['required', 'string', 'max:100'],
                'address.postal_code' => ['required', 'string', 'max:20'],
                'address.delivery_instructions' => ['nullable', 'string', 'max:5000'],
            ];
        }

        if ($this->boolean('portal.create') && $customer->user_id === null) {
            $rules['portal.password'] = ['required', 'string', Password::defaults()];
        }

        return $rules;
    }
}
