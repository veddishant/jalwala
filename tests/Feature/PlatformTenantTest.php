<?php

use App\Models\Tenant;
use App\Models\User;
use App\TenantStatus;

test('super admin can view platform dashboard', function () {
    seedRolesAndPermissions();

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->get(route('platform.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/dashboard')
            ->has('stats')
            ->has('recentTenants'));
});

test('supplier admin cannot access platform routes', function () {
    ['admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->get(route('platform.dashboard'))
        ->assertForbidden();
});

test('super admin can list tenants', function () {
    seedRolesAndPermissions();

    Tenant::factory()->count(3)->create();

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->get(route('platform.tenants.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/tenants/index')
            ->has('tenants.data', 3));
});

test('super admin can create a tenant from platform', function () {
    seedRolesAndPermissions();

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->post(route('platform.tenants.store'), [
            'business_name' => 'Acme Water Co',
            'slug' => 'acme-water',
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'admin_name' => 'Acme Admin',
            'admin_email' => 'admin@acme-water.test',
            'admin_phone' => '9876543210',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ])
        ->assertRedirect();

    $tenant = Tenant::query()->where('slug', 'acme-water')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->status)->toBe(TenantStatus::Active)
        ->and($tenant->subscription)->not->toBeNull();

    $admin = User::query()->where('email', 'admin@acme-water.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->tenant_id)->toBe($tenant->id)
        ->and($admin->hasRole('supplier-admin'))->toBeTrue();
});

test('super admin can suspend and reactivate a tenant', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->post(route('platform.tenants.suspend', $tenant))
        ->assertRedirect();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Suspended);

    $this->actingAs($superAdmin)
        ->post(route('platform.tenants.activate', $tenant))
        ->assertRedirect();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
});

test('super admin can impersonate an active tenant', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->post(route('platform.impersonate.store', $tenant))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('active_tenant_id', $tenant->id)
        ->assertSessionHas('impersonating_tenant', true);

    $this->actingAs($superAdmin)
        ->withSession([
            'active_tenant_id' => $tenant->id,
            'impersonating_tenant' => true,
        ])
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('super admin cannot impersonate a suspended tenant', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->suspended()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->post(route('platform.impersonate.store', $tenant))
        ->assertForbidden();
});

test('suspended tenant users are blocked from admin routes', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->suspended()->create();
    $admin = User::factory()->forTenant($tenant)->create();
    $admin->assignRole('supplier-admin');

    $this->actingAs($admin)
        ->get(route('admin.customers.index'))
        ->assertForbidden();
});

test('super admin dashboard redirect works from root dashboard', function () {
    seedRolesAndPermissions();

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertRedirect(route('platform.dashboard'));
});
