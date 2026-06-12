<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\TenantContext;
use App\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        TenantContext::resolveFromAuthenticatedUser($this->user());
    }

    public function authorize(): bool
    {
        return $this->user()?->can('users.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $managedUser = User::query()->findOrFail((int) $this->route('managedUser'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('tenant_id', TenantContext::getId())
                    ->ignore($managedUser->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', Password::defaults()],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'role' => ['required', 'string', Rule::in($this->assignableRoles($managedUser))],
        ];
    }

    /**
     * @return list<string>
     */
    private function assignableRoles(User $managedUser): array
    {
        $user = $this->user();

        if ($managedUser->hasRole('super-admin')) {
            return ['super-admin'];
        }

        if ($user?->hasRole('super-admin')) {
            return ['supplier-admin', 'delivery-agent', 'customer'];
        }

        return ['delivery-agent', 'customer'];
    }
}
