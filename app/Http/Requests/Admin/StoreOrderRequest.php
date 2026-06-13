<?php

namespace App\Http\Requests\Admin;

use App\Models\CustomerAddress;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('orders.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::getId() ?? $this->user()?->tenant_id;

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],
            'customer_address_id' => [
                'required',
                'integer',
                Rule::exists('customer_addresses', 'id')->where('tenant_id', $tenantId),
            ],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $customerId = $this->integer('customer_id');
            $addressId = $this->integer('customer_address_id');

            if ($customerId && $addressId) {
                $belongs = CustomerAddress::query()
                    ->where('id', $addressId)
                    ->where('customer_id', $customerId)
                    ->exists();

                if (! $belongs) {
                    $validator->errors()->add('customer_address_id', 'The selected address does not belong to this customer.');
                }
            }
        });
    }
}
