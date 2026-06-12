<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use App\UserStatus;

test('supplier admin can list tenant users', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    User::factory()->forTenant($tenant)->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users.data', 3));
});

test('supplier admin cannot see users from another tenant', function () {
    ['admin' => $admin] = createSupplierAdmin();

    User::factory()->forTenant(Tenant::factory()->create())->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

test('supplier admin can create a delivery agent', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Agent',
            'email' => 'new-agent@example.test',
            'phone' => '9876543210',
            'password' => 'password',
            'role' => 'delivery-agent',
        ])
        ->assertRedirect(route('admin.users.index'));

    $created = User::query()->where('email', 'new-agent@example.test')->first();

    expect($created)->not->toBeNull()
        ->and($created->tenant_id)->toBe($tenant->id)
        ->and($created->hasRole('delivery-agent'))->toBeTrue();
});

test('supplier admin can update user role and status', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '1234567890',
            'status' => UserStatus::Inactive->value,
            'role' => 'delivery-agent',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->status)->toBe(UserStatus::Inactive)
        ->and($user->hasRole('delivery-agent'))->toBeTrue();
});

test('supplier admin gets not found when editing another tenants user', function () {
    ['admin' => $admin] = createSupplierAdmin();

    $otherUser = User::factory()->forTenant(Tenant::factory()->create())->create();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $otherUser))
        ->assertNotFound();
});

test('tenant id is never taken from user input', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();
    $otherTenant = Tenant::factory()->create();

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Injected Tenant',
            'email' => 'injected@example.test',
            'password' => 'password',
            'role' => 'customer',
            'tenant_id' => $otherTenant->id,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->where('email', 'injected@example.test')->value('tenant_id'))
        ->toBe($tenant->id);
});
