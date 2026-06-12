<?php

namespace App\Http\Requests\Admin;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

class TopUpWalletRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('wallet.top-up') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
