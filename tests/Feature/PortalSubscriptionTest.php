<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionPauseService;
use App\Services\SubscriptionService;
use App\SubscriptionStatus;
use App\Support\TenantContext;

test('customer can view their subscription', function () {
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

    app(SubscriptionService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 2]],
        daysOfWeek: [1, 4],
        startDate: today()->toDateString(),
    );

    $this->actingAs($user)
        ->get(route('portal.subscription.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/subscription')
            ->has('subscription')
            ->has('upcomingDeliveries')
            ->where('can.pause', true)
            ->where('can.resume', false));
});

test('customer can pause their subscription from portal', function () {
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

    $subscription = app(SubscriptionService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        daysOfWeek: [2],
        startDate: today()->toDateString(),
    );

    $this->actingAs($user)
        ->post(route('portal.subscription.pause'), [
            'start_date' => today()->addDays(3)->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
            'reason' => 'Vacation',
        ])
        ->assertRedirect();

    expect($subscription->fresh()->pauses)->toHaveCount(1);
});

test('customer can resume their subscription from portal', function () {
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

    $subscription = app(SubscriptionService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        daysOfWeek: [2],
        startDate: today()->toDateString(),
    );

    app(SubscriptionPauseService::class)->pause(
        subscription: $subscription,
        startDate: today()->toDateString(),
        endDate: today()->addDays(5)->toDateString(),
        createdBy: $user->id,
    );

    $this->actingAs($user)
        ->post(route('portal.subscription.resume'))
        ->assertRedirect();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->fresh()->pauses)->toHaveCount(0);
});
