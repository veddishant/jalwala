<?php

use App\Models\Tenant;
use App\Models\User;

test('tenant user can access admin routes with their tenant context', function () {
    ['admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('super admin without active tenant is blocked when multiple tenants exist', function () {
    seedRolesAndPermissions();

    Tenant::factory()->count(2)->create();

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->flushSession();

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('super admin auto resolves tenant when only one active tenant exists', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->flushSession();

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('super admin with active tenant can access admin routes', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('delivery agent cannot access admin routes', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $agent = User::factory()->forTenant($tenant)->create();
    $agent->assignRole('delivery-agent');

    $this->actingAs($agent)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('customer can access portal dashboard', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $customer = User::factory()->forTenant($tenant)->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route('portal.dashboard'))
        ->assertOk();
});
