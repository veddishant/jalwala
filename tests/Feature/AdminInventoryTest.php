<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\OrderSource;
use App\OrderStatus;
use App\Services\CustomerOnboardingService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\WalletService;
use App\Support\TenantContext;

test('supplier admin can view warehouse inventory dashboard', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $product = Product::factory()->forTenant($tenant)->create(['is_returnable' => true]);

    TenantContext::setId($tenant->id);

    $warehouse = app(InventoryService::class)->ensureWarehouseLocation($tenant);

    app(InventoryService::class)->receiveStock(
        location: $warehouse,
        product: $product,
        quantity: 10,
        createdBy: $admin->id,
    );

    $this->actingAs($admin)
        ->get(route('admin.inventory.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/inventory/index')
            ->has('balances', 1)
            ->where('balances.0.filled_quantity', 10)
            ->where('can.receiveStock', true));
});

test('supplier admin can receive warehouse stock', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $product = Product::factory()->forTenant($tenant)->create(['is_returnable' => true]);

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.inventory.receive'), [
            'product_id' => $product->id,
            'quantity' => 25,
            'notes' => 'Supplier delivery',
        ])
        ->assertRedirect();

    $warehouse = app(InventoryService::class)->ensureWarehouseLocation($tenant);

    $balance = InventoryBalance::query()
        ->where('inventory_location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->first();

    expect($balance)->not->toBeNull()
        ->and($balance->filled_quantity)->toBe(25);
});

test('supplier admin can view customer jar inventory', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $product = Product::factory()->forTenant($tenant)->create(['is_returnable' => true]);

    TenantContext::setId($tenant->id);

    $location = app(InventoryService::class)->ensureCustomerLocation($customer);

    app(InventoryService::class)->adjust(
        location: $location,
        product: $product,
        jarType: 'filled',
        direction: 'increase',
        quantity: 3,
        reason: 'Initial jars',
        createdBy: $admin->id,
    );

    app(InventoryService::class)->adjust(
        location: $location,
        product: $product,
        jarType: 'empty',
        direction: 'increase',
        quantity: 1,
        reason: 'Empty jar on premises',
        createdBy: $admin->id,
    );

    $this->actingAs($admin)
        ->get(route('admin.customers.inventory', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/customers/inventory')
            ->has('summary', 1)
            ->where('summary.0.filled_quantity', 3)
            ->where('summary.0.empty_quantity', 1));
});

test('customer onboarding creates an inventory location', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    TenantContext::setId($tenant->id);

    $customer = app(CustomerOnboardingService::class)->onboard(
        data: [
            'name' => 'New Customer',
            'phone' => '9999999999',
            'address' => [
                'label' => 'Home',
                'address_line_1' => '1 Test Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400001',
            ],
        ],
        tenantId: $tenant->id,
        createdBy: $admin->id,
    );

    $location = app(InventoryService::class)->ensureCustomerLocation($customer);

    expect($location->locatable_id)->toBe($customer->id)
        ->and($location->isCustomerLocation())->toBeTrue();
});

test('delivering an order transfers filled jars from warehouse to customer', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create([
        'is_returnable' => true,
        'unit_price' => '30.00',
    ]);

    app(WalletService::class)->ensureForCustomer($customer, '500.00');

    TenantContext::setId($tenant->id);

    $inventoryService = app(InventoryService::class);
    $warehouse = $inventoryService->ensureWarehouseLocation($tenant);
    $customerLocation = $inventoryService->ensureCustomerLocation($customer);

    $inventoryService->receiveStock($warehouse, $product, 20, $admin->id);
    $inventoryService->adjust($customerLocation, $product, 'empty', 'increase', 1, 'Empty on premises', $admin->id);

    $order = app(OrderService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 2]],
        scheduledDate: now()->addDay()->toDateString(),
        source: OrderSource::Manual,
        createdBy: $admin->id,
    );

    app(OrderService::class)->confirm($order, $admin->id);
    app(OrderService::class)->transition($order, OrderStatus::Assigned, $admin->id);
    app(OrderService::class)->transition($order->fresh(), OrderStatus::OutForDelivery, $admin->id);

    app(OrderService::class)->transition(
        order: $order->fresh(),
        toStatus: OrderStatus::Delivered,
        changedBy: $admin->id,
        emptiesCollected: [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    );

    $warehouseBalance = InventoryBalance::query()
        ->where('inventory_location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    $customerBalance = InventoryBalance::query()
        ->where('inventory_location_id', $customerLocation->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect($warehouseBalance->filled_quantity)->toBe(18)
        ->and($warehouseBalance->empty_quantity)->toBe(1)
        ->and($customerBalance->filled_quantity)->toBe(2)
        ->and($customerBalance->empty_quantity)->toBe(0);

    expect(InventoryMovement::query()
        ->where('reference_type', 'order')
        ->where('reference_id', $order->id)
        ->count())->toBe(4);
});

test('delivery inventory transfer is idempotent', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create(['is_returnable' => true]);

    TenantContext::setId($tenant->id);

    $inventoryService = app(InventoryService::class);
    $warehouse = $inventoryService->ensureWarehouseLocation($tenant);
    $inventoryService->ensureCustomerLocation($customer);
    $inventoryService->receiveStock($warehouse, $product, 10, $admin->id);

    $order = app(OrderService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 1]],
        scheduledDate: now()->addDay()->toDateString(),
        source: OrderSource::Manual,
        createdBy: $admin->id,
    );

    $inventoryService->transferForDelivery($order->fresh(['items.product', 'customer']), [], $admin->id);
    $inventoryService->transferForDelivery($order->fresh(['items.product', 'customer']), [], $admin->id);

    expect(InventoryMovement::query()
        ->where('reference_type', 'order')
        ->where('reference_id', $order->id)
        ->count())->toBe(2);
});

test('customer closure settles inventory back to warehouse', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $product = Product::factory()->forTenant($tenant)->create(['is_returnable' => true]);

    TenantContext::setId($tenant->id);

    $inventoryService = app(InventoryService::class);
    $warehouse = $inventoryService->ensureWarehouseLocation($tenant);
    $customerLocation = $inventoryService->ensureCustomerLocation($customer);

    $inventoryService->adjust($customerLocation, $product, 'filled', 'increase', 2, 'On premises', $admin->id);
    $inventoryService->adjust($customerLocation, $product, 'empty', 'increase', 1, 'On premises', $admin->id);

    $inventoryService->settleOnCustomerClosure($customer, $admin->id);

    $customerBalance = InventoryBalance::query()
        ->where('inventory_location_id', $customerLocation->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    $warehouseBalance = InventoryBalance::query()
        ->where('inventory_location_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect($customerBalance->filled_quantity)->toBe(0)
        ->and($customerBalance->empty_quantity)->toBe(0)
        ->and($warehouseBalance->empty_quantity)->toBe(3);
});
