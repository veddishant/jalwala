<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DepositService;
use App\Support\TenantContext;

test('customer can view their deposits and ledger', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $user->id,
    ]);

    $product = Product::factory()->forTenant($tenant)->create([
        'deposit_amount' => '300.00',
        'is_returnable' => true,
    ]);

    $deposit = app(DepositService::class)->ensureForCustomer($customer);

    TenantContext::setId($tenant->id);

    app(DepositService::class)->collect(
        deposit: $deposit,
        product: $product,
        jarCount: 1,
        createdBy: $user->id,
    );

    $this->actingAs($user)
        ->get(route('portal.deposits.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/deposits')
            ->where('deposit.balance', '300.00')
            ->where('deposit.held_jar_count', 1)
            ->has('transactions.data', 1));
});
