<?php

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use App\UserStatus;

test('supplier admin can list tenant customers', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    Customer::factory()->forTenant($tenant)->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/index')
            ->has('customers.data', 2));
});

test('supplier admin cannot see customers from another tenant', function () {
    ['admin' => $admin] = createSupplierAdmin();

    Customer::factory()->forTenant(Tenant::factory()->create())->create();

    $this->actingAs($admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('customers.data', 0));
});

test('supplier admin can onboard a customer with address', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->post(route('admin.customers.store'), [
            'name' => 'Ravi Kumar',
            'phone' => '9876543210',
            'email' => 'ravi@example.test',
            'status' => CustomerStatus::Active->value,
            'notes' => 'Morning delivery preferred',
            'address' => [
                'label' => 'Home',
                'address_line_1' => '42 Lake View',
                'address_line_2' => null,
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'postal_code' => '411001',
                'delivery_instructions' => 'Ring the bell',
            ],
            'portal' => [
                'create' => false,
            ],
        ])
        ->assertRedirect(route('admin.customers.index'));

    $customer = Customer::query()
        ->where('email', 'ravi@example.test')
        ->with('defaultAddress')
        ->first();

    expect($customer)->not->toBeNull()
        ->and($customer->tenant_id)->toBe($tenant->id)
        ->and($customer->code)->toBe('CUST-0001')
        ->and($customer->defaultAddress)->not->toBeNull()
        ->and($customer->defaultAddress?->city)->toBe('Pune');
});

test('supplier admin can create portal account during onboarding', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->post(route('admin.customers.store'), [
            'name' => 'Portal Customer',
            'phone' => '9123456780',
            'email' => 'portal-customer@example.test',
            'status' => CustomerStatus::Active->value,
            'address' => [
                'label' => 'Home',
                'address_line_1' => '1 Main Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400001',
            ],
            'portal' => [
                'create' => true,
                'password' => 'password',
            ],
        ])
        ->assertRedirect(route('admin.customers.index'));

    $customer = Customer::query()->where('email', 'portal-customer@example.test')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->user_id)->not->toBeNull()
        ->and($customer->user?->hasRole('customer'))->toBeTrue()
        ->and($customer->user?->tenant_id)->toBe($tenant->id);
});

test('supplier admin can pause and resume a customer', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create([
        'status' => CustomerStatus::Active,
    ]);

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.customers.pause', $customer))
        ->assertRedirect();

    expect($customer->fresh()->status)->toBe(CustomerStatus::Paused);

    $this->actingAs($admin)
        ->post(route('admin.customers.resume', $customer))
        ->assertRedirect();

    expect($customer->fresh()->status)->toBe(CustomerStatus::Active);
});

test('supplier admin can close a customer and deactivate portal user', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $user->id,
        'status' => CustomerStatus::Active,
    ]);

    CustomerAddress::factory()->forCustomer($customer)->create();

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.customers.close', $customer), [
            'closure_reason' => 'Moved out of area',
        ])
        ->assertRedirect(route('admin.customers.index'));

    $customer->refresh();
    $user->refresh();

    expect($customer->status)->toBe(CustomerStatus::Closed)
        ->and($customer->closure_reason)->toBe('Moved out of area')
        ->and($user->status)->toBe(UserStatus::Inactive);
});

test('tenant id is never taken from customer input', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();
    $otherTenant = Tenant::factory()->create();

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.customers.store'), [
            'name' => 'Injected Tenant',
            'phone' => '9000000001',
            'status' => CustomerStatus::Active->value,
            'tenant_id' => $otherTenant->id,
            'address' => [
                'label' => 'Home',
                'address_line_1' => 'Test',
                'city' => 'Test',
                'state' => 'Test',
                'postal_code' => '000000',
            ],
        ])
        ->assertRedirect(route('admin.customers.index'));

    expect(Customer::query()->where('phone', '9000000001')->value('tenant_id'))
        ->toBe($tenant->id);
});
