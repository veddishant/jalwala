<?php

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\OrderStatus;

test('supplier admin can view admin dashboard with stats', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    Customer::factory()->forTenant($tenant)->create(['status' => CustomerStatus::Active]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->has('stats')
            ->has('recentOrders')
            ->has('lowWalletCustomers')
            ->where('stats.active_customers', 1));
});

test('dashboard route redirects supplier admin to admin dashboard', function () {
    ['admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

test('delivery agent cannot access admin dashboard', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $agent = User::factory()->forTenant($tenant)->create();
    $agent->assignRole('delivery-agent');

    $this->actingAs($agent)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin dashboard lists recent open orders', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();

    Order::factory()->forCustomer($customer)->create([
        'customer_address_id' => $address->id,
        'status' => OrderStatus::Pending,
        'scheduled_date' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentOrders', 1)
            ->where('stats.pending_orders', 1));
});
