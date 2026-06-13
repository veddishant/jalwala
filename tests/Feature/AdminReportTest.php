<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\User;
use App\OrderSource;
use App\OrderStatus;
use App\ReportType;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\ReportService;
use App\Services\WalletService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Cache;

test('supplier admin can view reports picker', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reports/index')
            ->has('reports', 6));
});

test('supplier admin can view sales report', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();
    $address = CustomerAddress::factory()->forCustomer($customer)->create();
    $product = Product::factory()->forTenant($tenant)->create(['unit_price' => '40.00']);

    app(WalletService::class)->ensureForCustomer($customer, '500.00');

    TenantContext::setId($tenant->id);

    $inventoryService = app(InventoryService::class);
    $warehouse = $inventoryService->ensureWarehouseLocation($tenant);
    $inventoryService->receiveStock($warehouse, $product, 10, $admin->id);

    $order = app(OrderService::class)->create(
        customer: $customer,
        address: $address,
        items: [['product_id' => $product->id, 'quantity' => 2]],
        scheduledDate: today()->toDateString(),
        source: OrderSource::Manual,
        createdBy: $admin->id,
    );

    app(OrderService::class)->confirm($order, $admin->id);
    app(OrderService::class)->transition($order->fresh(), OrderStatus::Assigned, $admin->id);
    app(OrderService::class)->transition($order->fresh(), OrderStatus::OutForDelivery, $admin->id);
    app(OrderService::class)->transition($order->fresh(), OrderStatus::Delivered, $admin->id);
    app(OrderService::class)->transition($order->fresh(), OrderStatus::Completed, $admin->id);

    $this->actingAs($admin)
        ->get(route('admin.reports.show', 'sales', [
            'date_from' => today()->subDays(7)->toDateString(),
            'date_to' => today()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reports/show')
            ->where('reportType.type', 'sales')
            ->has('filters')
            ->loadDeferredProps(fn ($reload) => $reload
                ->where('report.summary.order_count', 1)
                ->where('report.summary.total_revenue', '80.00')));
});

test('supplier admin can export sales report as csv', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->get(route('admin.reports.export', 'sales', [
            'date_from' => today()->subDays(7)->toDateString(),
            'date_to' => today()->toDateString(),
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('sales report results are cached', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    TenantContext::setId($tenant->id);

    Cache::flush();

    $filters = [
        'date_from' => today()->subDays(7)->toDateString(),
        'date_to' => today()->toDateString(),
        'grain' => 'daily',
        'customer_id' => null,
        'product_id' => null,
        'source' => null,
        'agent_id' => null,
    ];

    $service = app(ReportService::class);

    $first = $service->generate(ReportType::Sales, $tenant->id, $filters);
    $second = $service->generate(ReportType::Sales, $tenant->id, $filters);

    expect($first)->toBe($second);
});

test('delivery agent cannot access reports', function () {
    ['tenant' => $tenant] = createSupplierAdmin();

    $agent = User::factory()->forTenant($tenant)->create();
    $agent->assignRole('delivery-agent');

    TenantContext::setId($tenant->id);

    $this->actingAs($agent)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});

test('supplier admin can view outstanding balances report', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $customer = Customer::factory()->forTenant($tenant)->create();

    TenantContext::setId($tenant->id);

    $wallet = app(WalletService::class)->ensureForCustomer($customer);
    $wallet->update(['balance' => '-50.00']);

    $this->actingAs($admin)
        ->get(route('admin.reports.show', 'outstanding'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reports/show')
            ->loadDeferredProps(fn ($reload) => $reload
                ->where('report.summary.customer_count', 1)
                ->where('report.summary.total_owed', '50.00')));
});
