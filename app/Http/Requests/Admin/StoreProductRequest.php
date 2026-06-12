<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\ProductStatus;
use App\ProductType;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::getId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->where('tenant_id', $tenantId),
            ],
            'type' => ['required', 'string', Rule::enum(ProductType::class)],
            'capacity_liters' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'deposit_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_returnable' => ['sometimes', 'boolean'],
            'status' => ['required', 'string', Rule::enum(ProductStatus::class)],
        ];
    }
}
