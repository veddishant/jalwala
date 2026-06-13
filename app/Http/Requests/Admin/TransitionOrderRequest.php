<?php

namespace App\Http\Requests\Admin;

use App\OrderStatus;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('orders.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(array_column(OrderStatus::cases(), 'value')),
                Rule::notIn([
                    OrderStatus::Draft->value,
                    OrderStatus::Cancelled->value,
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
