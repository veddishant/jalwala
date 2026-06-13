<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\OrderSource;
use App\OrderStatus;
use App\Services\OrderService;
use App\Services\WalletService;
use App\Support\TenantContext;

test('customer can place an order from the portal', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $user->id,
    ]);

    CustomerAddress::factory()->forCustomer($customer)->create([
        'is_default' => true,
    ]);

    $product = Product::factory()->forTenant($tenant)->create(['unit_price' => '30.00']);

    app(WalletService::class)->ensureForCustomer($customer, '300.00');

    TenantContext::setId($tenant->id);

    $this->actingAs($user)
        ->post(route('portal.orders.store'), [
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])
        ->assertRedirect();

    $order = Order::query()->where('customer_id', $customer->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->total)->toBe('60.00')
        ->and($order->wallet_amount_charged)->toBe('60.00');

    $wallet = Wallet::query()->where('customer_id', $customer->id)->first();

    expect($wallet->balance)->toBe('240.00');
});

test('customer can view their orders', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $user->id,
    ]);

    $address = CustomerAddress::factory()->forCustomer($customer)->create();

    $product = Product::factory()->forTenant($tenant)->create();

    TenantContext::setId($tenant->id);

    app(OrderService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        scheduledDate: now()->addDay()->toDateString(),
        source: OrderSource::CustomerPortal,
        createdBy: $user->id,
    );

    $this->actingAs($user)
        ->get(route('portal.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/orders/index')
            ->has('orders.data', 1));
});

test('customer can cancel their pending order', function () {
    seedRolesAndPermissions();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $user->assignRole('customer');

    $customer = Customer::factory()->forTenant($tenant)->create([
        'user_id' => $user->id,
    ]);

    $address = CustomerAddress::factory()->forCustomer($customer)->create();

    $product = Product::factory()->forTenant($tenant)->create(['unit_price' => '25.00']);

    app(WalletService::class)->ensureForCustomer($customer, '100.00');

    TenantContext::setId($tenant->id);

    $orderService = app(OrderService::class);

    $order = $orderService->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 2]],
        scheduledDate: now()->addDay()->toDateString(),
        source: OrderSource::CustomerPortal,
        createdBy: $user->id,
    );

    $orderService->confirm($order, $user->id);

    $this->actingAs($user)
        ->post(route('portal.orders.cancel', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});
