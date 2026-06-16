<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WalletService;

test('customer can view portal dashboard with account summary', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $customerUser = User::factory()->forTenant($tenant)->create();
    $customerUser->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $customerUser->id,
        'name' => 'Portal Customer',
        'code' => 'CUST-0099',
    ]);

    app(WalletService::class)->ensureForCustomer(
        customer: $customer,
        openingBalance: '250.00',
        lowBalanceThreshold: '100.00',
    );

    $this->actingAs($customerUser)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/dashboard')
            ->has('summary')
            ->has('recentOrders')
            ->where('summary.customer.code', 'CUST-0099')
            ->where('summary.wallet.balance', '250.00'));
});

test('dashboard route redirects customer to portal dashboard', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $customerUser = User::factory()->forTenant($tenant)->create();
    $customerUser->assignRole('customer');

    Customer::factory()->forTenant($tenant)->create([
        'user_id' => $customerUser->id,
    ]);

    $this->actingAs($customerUser)
        ->get(route('dashboard'))
        ->assertRedirect(route('portal.dashboard'));
});
