<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\LowWalletBalanceNotification;
use App\Services\WalletService;
use App\Support\TenantContext;
use App\WalletTransactionCategory;
use App\WalletTransactionType;
use Illuminate\Support\Facades\Notification;

test('supplier admin can view customer wallet and ledger', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    app(WalletService::class)->ensureForCustomer($customer, '200.00');

    $this->actingAs($admin)
        ->get(route('admin.customers.wallet', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/wallet')
            ->where('wallet.balance', '200.00')
            ->has('transactions.data', 1));
});

test('supplier admin can top up a customer wallet', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $wallet = app(WalletService::class)->ensureForCustomer($customer, '100.00');

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.customers.wallet.top-up', $customer), [
            'amount' => 50,
            'description' => 'Cash payment',
        ])
        ->assertRedirect();

    expect($wallet->fresh()->balance)->toBe('150.00');

    expect(WalletTransaction::query()
        ->where('wallet_id', $wallet->id)
        ->where('category', WalletTransactionCategory::TopUp)
        ->exists())->toBeTrue();
});

test('supplier admin can adjust wallet and allow negative balance', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $wallet = app(WalletService::class)->ensureForCustomer($customer, '50.00');

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.customers.wallet.adjust', $customer), [
            'amount' => 75,
            'direction' => WalletTransactionType::Debit->value,
            'reason' => 'Correction for over-credit',
        ])
        ->assertRedirect();

    expect($wallet->fresh()->balance)->toBe('-25.00');
});

test('wallet credit is idempotent by key', function () {
    ['tenant' => $tenant] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $wallet = app(WalletService::class)->ensureForCustomer($customer);

    TenantContext::setId($tenant->id);

    $service = app(WalletService::class);

    $first = $service->credit(
        wallet: $wallet,
        amount: '100.00',
        category: WalletTransactionCategory::TopUp,
        idempotencyKey: 'duplicate-key',
    );

    $second = $service->credit(
        wallet: $wallet,
        amount: '100.00',
        category: WalletTransactionCategory::TopUp,
        idempotencyKey: 'duplicate-key',
    );

    expect($first->id)->toBe($second->id)
        ->and($wallet->fresh()->balance)->toBe('100.00')
        ->and(WalletTransaction::query()->where('wallet_id', $wallet->id)->count())->toBe(1);
});

test('low balance notification is sent when balance drops below threshold', function () {
    Notification::fake();

    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $user->id,
    ]);
    $wallet = app(WalletService::class)->ensureForCustomer(
        customer: $customer,
        openingBalance: '150.00',
        lowBalanceThreshold: '100.00',
        createdBy: $admin->id,
    );

    TenantContext::setId($tenant->id);

    app(WalletService::class)->debit(
        wallet: $wallet,
        amount: '60.00',
        category: WalletTransactionCategory::Adjustment,
        idempotencyKey: 'debit-low-balance-test',
        description: 'Test debit',
        createdBy: $admin->id,
    );

    $customer->load('user');

    Notification::assertSentTo($customer->user, LowWalletBalanceNotification::class);
});

test('supplier admin cannot access wallet for another tenants customer', function () {
    ['admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant(Tenant::factory()->create())->create();

    $this->actingAs($admin)
        ->get(route('admin.customers.wallet', $customer))
        ->assertNotFound();
});
