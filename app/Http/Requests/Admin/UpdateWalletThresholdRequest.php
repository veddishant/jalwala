<?php

namespace App\Http\Requests\Admin;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletThresholdRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('wallet.adjust') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'low_balance_threshold' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
