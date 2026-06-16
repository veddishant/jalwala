<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.tenants.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branding.logo_url' => ['nullable', 'string', 'max:2048', 'url'],
            'branding.primary_color' => ['nullable', 'string', 'max:32', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'branding.support_email' => ['nullable', 'string', 'email', 'max:255'],
            'branding.support_phone' => ['nullable', 'string', 'max:20'],
            'notifications.from_name' => ['nullable', 'string', 'max:255'],
            'domain.custom_domain' => ['nullable', 'string', 'max:255'],
        ];
    }
}
