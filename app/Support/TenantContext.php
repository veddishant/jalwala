<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class TenantContext
{
    private static ?int $tenantId = null;

    private static bool $bypassed = false;

    public static function set(?Tenant $tenant): void
    {
        self::$tenantId = $tenant?->id;
    }

    public static function setId(?int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function getId(): ?int
    {
        return self::$tenantId;
    }

    public static function get(): ?Tenant
    {
        if (self::$tenantId === null) {
            return null;
        }

        return once(fn (): ?Tenant => Tenant::query()->find(self::$tenantId));
    }

    public static function bypass(bool $bypass = true): void
    {
        self::$bypassed = $bypass;
    }

    public static function isBypassed(): bool
    {
        return self::$bypassed;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
        self::$bypassed = false;
    }

    public static function resolveFromAuthenticatedUser(?Authenticatable $user): void
    {
        if (self::$tenantId !== null || self::$bypassed || $user === null) {
            return;
        }

        if ($user instanceof User && $user->tenant_id !== null) {
            self::setId((int) $user->tenant_id);
        }
    }
}
