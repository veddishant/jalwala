<?php

use App\DepositTransactionType;
use App\Models\Customer;
use App\Models\DepositTransaction;
use App\Models\Product;
use App\Services\DepositService;
use App\Support\TenantContext;

test('supplier admin can view customer deposits and ledger', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $product = Product::factory()->forTenant($tenant)->create([
        'deposit_amount' => '300.00',
        'is_returnable' => true,
    ]);

    $deposit = app(DepositService::class)->ensureForCustomer($customer);

    TenantContext::setId($tenant->id);

    app(DepositService::class)->collect(
        deposit: $deposit,
        product: $product,
        jarCount: 2,
        createdBy: $admin->id,
    );

    $this->actingAs($admin)
        ->get(route('admin.customers.deposits', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/deposits')
            ->where('deposit.balance', '600.00')
            ->where('deposit.held_jar_count', 2)
            ->has('transactions.data', 1));
});

test('supplier admin can collect a deposit', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $product = Product::factory()->forTenant($tenant)->create([
        'deposit_amount' => '250.00',
        'is_returnable' => true,
    ]);

    $deposit = app(DepositService::class)->ensureForCustomer($customer);

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.customers.deposits.collect', $customer), [
            'product_id' => $product->id,
            'jar_count' => 3,
            'description' => 'Signup deposit',
        ])
        ->assertRedirect();

    expect($deposit->fresh()->balance)->toBe('750.00');

    expect(DepositTransaction::query()
        ->where('customer_deposit_id', $deposit->id)
        ->where('type', DepositTransactionType::Collect)
        ->exists())->toBeTrue();
});

test('supplier admin can refund part of a deposit', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $product = Product::factory()->forTenant($tenant)->create([
        'deposit_amount' => '300.00',
        'is_returnable' => true,
    ]);

    $deposit = app(DepositService::class)->ensureForCustomer($customer);

    TenantContext::setId($tenant->id);

    app(DepositService::class)->collect(
        deposit: $deposit,
        product: $product,
        jarCount: 2,
        createdBy: $admin->id,
    );

    $this->actingAs($admin)
        ->post(route('admin.customers.deposits.refund', $customer), [
            'jar_count' => 1,
            'amount' => 300,
            'description' => 'One jar returned',
        ])
        ->assertRedirect();

    expect($deposit->fresh()->balance)->toBe('300.00');
});

test('supplier admin can adjust deposit balance', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $deposit = app(DepositService::class)->ensureForCustomer($customer);

    TenantContext::setId($tenant->id);

    app(DepositService::class)->collect(
        deposit: $deposit,
        product: Product::factory()->forTenant($tenant)->create([
            'deposit_amount' => '200.00',
            'is_returnable' => true,
        ]),
        jarCount: 1,
        createdBy: $admin->id,
    );

    $this->actingAs($admin)
        ->post(route('admin.customers.deposits.adjust', $customer), [
            'amount' => 50,
            'direction' => 'decrease',
            'reason' => 'Waived partial deposit',
        ])
        ->assertRedirect();

    expect($deposit->fresh()->balance)->toBe('150.00');
});

test('closing a customer refunds all held deposits', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $product = Product::factory()->forTenant($tenant)->create([
        'deposit_amount' => '300.00',
        'is_returnable' => true,
    ]);

    $deposit = app(DepositService::class)->ensureForCustomer($customer);

    TenantContext::setId($tenant->id);

    app(DepositService::class)->collect(
        deposit: $deposit,
        product: $product,
        jarCount: 2,
        createdBy: $admin->id,
    );

    $this->actingAs($admin)
        ->post(route('admin.customers.close', $customer))
        ->assertRedirect(route('admin.customers.index'));

    expect($deposit->fresh()->balance)->toBe('0.00');

    expect(DepositTransaction::query()
        ->where('customer_deposit_id', $deposit->id)
        ->where('type', DepositTransactionType::Refund)
        ->count())->toBe(1);
});
