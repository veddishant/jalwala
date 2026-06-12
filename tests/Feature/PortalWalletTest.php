<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WalletService;

test('customer can view their wallet and transactions', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $user->id,
    ]);

    app(WalletService::class)->ensureForCustomer($customer, '250.00');

    $this->actingAs($user)
        ->get(route('portal.wallet.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/wallet')
            ->where('wallet.balance', '250.00')
            ->has('transactions.data', 1));
});

test('customer onboarding creates a wallet with opening balance', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->post(route('admin.customers.store'), [
            'name' => 'Wallet Customer',
            'phone' => '9000000099',
            'email' => 'wallet-customer@example.test',
            'status' => 'active',
            'address' => [
                'label' => 'Home',
                'address_line_1' => '1 Test Lane',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'postal_code' => '411001',
            ],
            'wallet' => [
                'opening_balance' => 300,
                'low_balance_threshold' => 50,
            ],
        ])
        ->assertRedirect(route('admin.customers.index'));

    $customer = Customer::query()->where('email', 'wallet-customer@example.test')->with('wallet')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->wallet)->not->toBeNull()
        ->and($customer->wallet->balance)->toBe('300.00')
        ->and($customer->wallet->low_balance_threshold)->toBe('50.00');
});
