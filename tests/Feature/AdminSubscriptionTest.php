<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\OrderSource;
use App\OrderStatus;
use App\Services\SubscriptionOrderGeneratorService;
use App\Services\SubscriptionPauseService;
use App\Services\SubscriptionService;
use App\Services\WalletService;
use App\SubscriptionStatus;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;

test('supplier admin can create a subscription', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create(['unit_price' => '25.00']);

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.subscriptions.store'), [
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'start_date' => today()->toDateString(),
            'days_of_week' => [1, 3, 5],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])
        ->assertRedirect();

    $subscription = Subscription::query()->where('customer_id', $customer->id)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->schedules)->toHaveCount(3)
        ->and($subscription->items)->toHaveCount(1);
});

test('subscription order generator creates and confirms orders', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create(['unit_price' => '30.00']);

    app(WalletService::class)->ensureForCustomer($customer, '500.00');

    TenantContext::setId($tenant->id);

    $targetDate = today()->next(Carbon::MONDAY)->toDateString();
    $dayOfWeek = Carbon::parse($targetDate)->dayOfWeek;

    $subscription = app(SubscriptionService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        daysOfWeek: [$dayOfWeek],
        startDate: today()->toDateString(),
    );

    $count = app(SubscriptionOrderGeneratorService::class)->generateForTenant(
        $tenant,
        $targetDate,
    );

    expect($count)->toBe(1);

    $order = Order::query()
        ->where('subscription_id', $subscription->id)
        ->whereDate('scheduled_date', $targetDate)
        ->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->source)->toBe(OrderSource::Subscription)
        ->and($order->total)->toBe('30.00');
});

test('subscription generator skips paused dates', function () {
    ['tenant' => $tenant] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create();

    TenantContext::setId($tenant->id);

    $targetDate = today()->next(Carbon::MONDAY)->toDateString();
    $dayOfWeek = Carbon::parse($targetDate)->dayOfWeek;

    $subscription = app(SubscriptionService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        daysOfWeek: [$dayOfWeek],
        startDate: today()->toDateString(),
    );

    app(SubscriptionPauseService::class)->pause(
        subscription: $subscription,
        startDate: $targetDate,
        endDate: $targetDate,
    );

    $subscription->update(['status' => SubscriptionStatus::Active]);

    $count = app(SubscriptionOrderGeneratorService::class)->generateForTenant(
        $tenant,
        $targetDate,
    );

    expect($count)->toBe(0);
});

test('active subscription detail shows pause but not resume action', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create();

    TenantContext::setId($tenant->id);

    $subscription = app(SubscriptionService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        daysOfWeek: [1],
        startDate: today()->toDateString(),
    );

    $this->actingAs($admin)
        ->get(route('admin.subscriptions.show', $subscription))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/subscriptions/show')
            ->where('can.pause', true)
            ->where('can.resume', false));
});

test('supplier admin can pause and resume a subscription', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create();

    TenantContext::setId($tenant->id);

    $subscription = app(SubscriptionService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        daysOfWeek: [1],
        startDate: today()->toDateString(),
    );

    $this->actingAs($admin)
        ->post(route('admin.subscriptions.pause', $subscription), [
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(7)->toDateString(),
            'reason' => 'Holiday',
        ])
        ->assertRedirect();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Paused);

    $this->actingAs($admin)
        ->post(route('admin.subscriptions.resume', $subscription))
        ->assertRedirect();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});
