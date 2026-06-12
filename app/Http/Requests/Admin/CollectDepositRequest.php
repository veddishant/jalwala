<?php

namespace App\Http\Requests\Admin;

use App\ProductStatus;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectDepositRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('deposits.collect') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::getId() ?? $this->user()?->tenant_id;

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('is_returnable', true)
                        ->where('status', ProductStatus::Active->value)),
            ],
            'jar_count' => ['required', 'integer', 'min:1', 'max:999'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
