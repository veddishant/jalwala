<?php

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Tenant;
use App\Models\User;

test('customer can view and update their profile', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create(['slug' => 'acme-water']);
    $user = User::factory()->forTenant($tenant)->create([
        'email' => 'profile@example.test',
    ]);
    $user->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $user->id,
        'email' => 'profile@example.test',
        'status' => CustomerStatus::Active,
    ]);

    CustomerAddress::factory()->forCustomer($customer)->create([
        'label' => 'Home',
        'address_line_1' => 'Old address',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'postal_code' => '411001',
    ]);

    $this->actingAs($user)
        ->get(route('portal.profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/profile')
            ->where('customer.name', $customer->name));

    $this->actingAs($user)
        ->put(route('portal.profile.update'), [
            'name' => 'Updated Customer',
            'phone' => '9988776655',
            'email' => 'profile@example.test',
            'address' => [
                'label' => 'Office',
                'address_line_1' => 'New address',
                'address_line_2' => null,
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400001',
                'delivery_instructions' => 'Leave at reception',
            ],
        ])
        ->assertRedirect(route('portal.profile.edit'));

    $customer->refresh();

    expect($customer->name)->toBe('Updated Customer')
        ->and($customer->defaultAddress?->address_line_1)->toBe('New address')
        ->and($user->fresh()->name)->toBe('Updated Customer');
});

test('guest can self register as a customer for an active tenant', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create(['slug' => 'self-signup']);

    $this->post(route('portal.register.store', $tenant), [
        'name' => 'Self Signup',
        'phone' => '9111222333',
        'email' => 'self@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'address' => [
            'label' => 'Home',
            'address_line_1' => '99 Park Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110001',
        ],
    ])
        ->assertRedirect(route('portal.dashboard'));

    $customer = Customer::query()->where('email', 'self@example.test')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->tenant_id)->toBe($tenant->id)
        ->and($customer->user?->hasRole('customer'))->toBeTrue()
        ->and($customer->defaultAddress)->not->toBeNull();

    $this->assertAuthenticatedAs($customer->user);
});

test('guest cannot register against an inactive tenant', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->suspended()->create(['slug' => 'inactive-tenant']);

    $this->get(route('portal.register', $tenant))->assertNotFound();
});
