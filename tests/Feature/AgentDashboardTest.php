<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\OrderStatus;

test('delivery agent can view agent dashboard', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $agent = User::factory()->forTenant($tenant)->create();
    $agent->assignRole('delivery-agent');

    $this->actingAs($agent)
        ->get(route('agent.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('agent/dashboard')
            ->has('stats')
            ->has('todayDeliveries'));
});

test('dashboard route redirects delivery agent to agent dashboard', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $agent = User::factory()->forTenant($tenant)->create();
    $agent->assignRole('delivery-agent');

    $this->actingAs($agent)
        ->get(route('dashboard'))
        ->assertRedirect(route('agent.dashboard'));
});

test('agent dashboard shows today scheduled deliveries', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $agent = User::factory()->forTenant($tenant)->create();
    $agent->assignRole('delivery-agent');

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();

    Order::factory()->forCustomer($customer)->create([
        'customer_address_id' => $address->id,
        'status' => OrderStatus::Assigned,
        'scheduled_date' => now()->toDateString(),
    ]);

    $this->actingAs($agent)
        ->get(route('agent.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('todayDeliveries', 1)
            ->where('stats.today_deliveries', 1));
});
