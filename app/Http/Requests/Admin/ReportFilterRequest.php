<?php

namespace App\Http\Requests\Admin;

use App\OrderSource;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'grain' => ['nullable', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'source' => ['nullable', 'string', Rule::in(array_column(OrderSource::cases(), 'value'))],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array{
     *     date_from: string,
     *     date_to: string,
     *     grain: string,
     *     customer_id: int|null,
     *     product_id: int|null,
     *     source: string|null,
     *     agent_id: int|null
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'date_from' => $validated['date_from'] ?? now()->subDays(30)->toDateString(),
            'date_to' => $validated['date_to'] ?? now()->toDateString(),
            'grain' => $validated['grain'] ?? 'daily',
            'customer_id' => isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            'product_id' => isset($validated['product_id']) ? (int) $validated['product_id'] : null,
            'source' => $validated['source'] ?? null,
            'agent_id' => isset($validated['agent_id']) ? (int) $validated['agent_id'] : null,
        ];
    }
}
