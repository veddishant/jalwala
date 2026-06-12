<?php

namespace App\Http\Requests\Admin;

use App\Support\TenantContext;
use App\WalletTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustWalletRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'direction' => ['required', 'string', Rule::enum(WalletTransactionType::class)],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
