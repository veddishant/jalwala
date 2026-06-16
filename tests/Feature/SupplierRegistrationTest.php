<?php

use App\Models\Tenant;
use App\Models\User;
use App\TenantStatus;

test('guest can view supplier registration page', function () {
    $this->get(route('supplier.register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('supplier/register'));
});

test('guest can register a new supplier tenant', function () {
    seedRolesAndPermissions();

    $this->post(route('supplier.register.store'), [
        'business_name' => 'Fresh Springs',
        'slug' => 'fresh-springs',
        'timezone' => 'Asia/Kolkata',
        'currency' => 'INR',
        'name' => 'Fresh Owner',
        'email' => 'owner@fresh-springs.test',
        'phone' => '9123456789',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertRedirect(route('admin.dashboard'));

    $tenant = Tenant::query()->where('slug', 'fresh-springs')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->status)->toBe(TenantStatus::Active)
        ->and($tenant->subscription)->not->toBeNull();

    $user = User::query()->where('email', 'owner@fresh-springs.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->tenant_id)->toBe($tenant->id)
        ->and($user->hasRole('supplier-admin'))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('supplier registration generates slug when not provided', function () {
    seedRolesAndPermissions();

    $this->post(route('supplier.register.store'), [
        'business_name' => 'Blue Drop Delivery',
        'name' => 'Blue Owner',
        'email' => 'owner@blue-drop.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertRedirect(route('admin.dashboard'));

    $tenant = Tenant::query()->where('name', 'Blue Drop Delivery')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->slug)->toBe('blue-drop-delivery');
});

test('supplier registration validates unique email', function () {
    seedRolesAndPermissions();

    ['admin' => $admin] = createSupplierAdmin();

    $this->post(route('supplier.register.store'), [
        'business_name' => 'Another Co',
        'name' => 'Duplicate',
        'email' => $admin->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertSessionHasErrors('email');
});
