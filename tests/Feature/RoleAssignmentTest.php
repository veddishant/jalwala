<?php

use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('roles and permissions seeder creates all roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->pluck('name')->sort()->values()->all())->toBe([
        'customer',
        'delivery-agent',
        'super-admin',
        'supplier-admin',
    ]);
});

test('super admin receives all permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $superAdminRole = Role::query()->where('name', 'super-admin')->firstOrFail();

    expect($superAdminRole->permissions)->toHaveCount(Permission::query()->count());
});

test('supplier admin cannot assign super admin role', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    TenantContext::setId($tenant->id);

    $response = $this->actingAs($admin)
        ->from(route('admin.users.edit', $user))
        ->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
            'role' => 'super-admin',
        ]);

    expect($response->status())->toBeIn([302, 422])
        ->and($user->refresh()->hasRole('super-admin'))->toBeFalse()
        ->and($user->hasRole('customer'))->toBeTrue();
});

test('database seeder assigns expected demo roles', function () {
    $this->seed(DatabaseSeeder::class);

    TenantContext::bypass();

    expect(User::query()->where('email', 'jalwala@yopmail.com')->first()?->hasRole('super-admin'))->toBeTrue();
    expect(User::query()->where('email', 'admin@demo-water-supply.test')->first()?->hasRole('supplier-admin'))->toBeTrue();
    expect(User::query()->where('email', 'agent@demo-water-supply.test')->first()?->hasRole('delivery-agent'))->toBeTrue();
    expect(User::query()->where('email', 'customer@demo-water-supply.test')->first()?->hasRole('customer'))->toBeTrue();
});
