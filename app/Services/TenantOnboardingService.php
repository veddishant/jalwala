<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\TenantContext;
use App\TenantStatus;
use App\TenantSubscriptionStatus;
use App\UserStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantOnboardingService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array{
     *     business_name: string,
     *     slug?: string|null,
     *     timezone?: string,
     *     currency?: string,
     *     admin: array{
     *         name: string,
     *         email: string,
     *         phone?: string|null,
     *         password: string
     *     },
     *     settings?: array<string, mixed>
     * }  $data
     * @return array{tenant: Tenant, admin: User}
     */
    public function onboard(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $slug = filled($data['slug'] ?? null)
                ? $this->ensureUniqueSlug((string) $data['slug'])
                : $this->generateSlugFromName($data['business_name']);

            $tenant = Tenant::query()->create([
                'name' => $data['business_name'],
                'slug' => $slug,
                'timezone' => $data['timezone'] ?? 'Asia/Kolkata',
                'currency' => $data['currency'] ?? 'INR',
                'settings' => $this->mergeSettings($data['settings'] ?? []),
                'status' => TenantStatus::Active,
            ]);

            $admin = User::query()->create([
                'name' => $data['admin']['name'],
                'email' => $data['admin']['email'],
                'phone' => $data['admin']['phone'] ?? null,
                'password' => Hash::make($data['admin']['password']),
                'status' => UserStatus::Active,
            ]);

            $admin->forceFill(['tenant_id' => $tenant->id])->save();
            $admin->assignRole('supplier-admin');

            TenantContext::setId($tenant->id);
            $this->inventoryService->ensureWarehouseLocation($tenant);

            $trialDays = (int) config('tenancy.trial_days', 14);
            $trialEndsAt = now()->addDays($trialDays);

            TenantSubscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan' => 'trial',
                'status' => TenantSubscriptionStatus::Trialing,
                'trial_ends_at' => $trialEndsAt,
                'current_period_ends_at' => $trialEndsAt,
            ]);

            return [
                'tenant' => $tenant->load('subscription'),
                'admin' => $admin,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(): array
    {
        return [
            'branding' => [
                'logo_url' => null,
                'primary_color' => '#0ea5e9',
                'support_email' => null,
                'support_phone' => null,
            ],
            'notifications' => [
                'from_name' => null,
            ],
            'domain' => [
                'custom_domain' => null,
                'verified' => false,
            ],
            'billing' => [
                'plan' => 'trial',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function mergeSettings(array $overrides): array
    {
        return array_replace_recursive($this->defaultSettings(), $overrides);
    }

    public function generateSlugFromName(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'supplier';
        }

        return $this->ensureUniqueSlug($base);
    }

    public function ensureUniqueSlug(string $slug): string
    {
        $candidate = Str::slug($slug);
        $original = $candidate;
        $suffix = 1;

        while (Tenant::query()->where('slug', $candidate)->exists()) {
            $candidate = $original.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
