<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\Wallet;
use App\OrderSource;
use App\OrderStatus;
use App\Services\OrderService;
use App\Services\WalletService;
use App\Support\TenantContext;

test('supplier admin can view orders list', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create(['unit_price' => '25.00']);

    TenantContext::setId($tenant->id);

    app(OrderService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 2]],
        scheduledDate: now()->addDay()->toDateString(),
        source: OrderSource::Manual,
        createdBy: $admin->id,
    );

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/orders/index')
            ->has('orders.data', 1));
});

test('supplier admin can create and confirm an order with wallet debit', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create(['unit_price' => '40.00']);

    app(WalletService::class)->ensureForCustomer($customer, '500.00');

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.orders.store'), [
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ])
        ->assertRedirect();

    $order = Order::query()->where('customer_id', $customer->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Draft)
        ->and($order->total)->toBe('120.00');

    $this->actingAs($admin)
        ->post(route('admin.orders.confirm', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->fresh()->wallet_amount_charged)->toBe('120.00');

    $wallet = Wallet::query()->where('customer_id', $customer->id)->first();

    expect($wallet->balance)->toBe('380.00');
});

test('supplier admin can cancel a pending order and refund wallet', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create(['unit_price' => '50.00']);

    app(WalletService::class)->ensureForCustomer($customer, '200.00');

    TenantContext::setId($tenant->id);

    $order = app(OrderService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 2]],
        scheduledDate: now()->addDay()->toDateString(),
        source: OrderSource::Manual,
        createdBy: $admin->id,
    );

    app(OrderService::class)->confirm($order, $admin->id);

    $this->actingAs($admin)
        ->post(route('admin.orders.cancel', $order), [
            'cancellation_reason' => 'Customer requested cancellation',
        ])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->fresh()->wallet_amount_charged)->toBe('0.00');

    $wallet = Wallet::query()->where('customer_id', $customer->id)->first();

    expect($wallet->balance)->toBe('200.00');
});

test('supplier admin can advance order through delivery statuses', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create();

    TenantContext::setId($tenant->id);

    $order = app(OrderService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        scheduledDate: now()->addDay()->toDateString(),
        source: OrderSource::Manual,
        createdBy: $admin->id,
    );

    app(OrderService::class)->confirm($order, $admin->id);

    $this->actingAs($admin)
        ->post(route('admin.orders.transition', $order), [
            'status' => OrderStatus::Assigned->value,
        ])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Assigned);
});
