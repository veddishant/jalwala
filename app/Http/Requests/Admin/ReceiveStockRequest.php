<?php

namespace App\Http\Requests\Admin;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('inventory.adjust') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
