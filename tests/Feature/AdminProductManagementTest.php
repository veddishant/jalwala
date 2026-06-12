<?php

use App\Models\Product;
use App\Models\Tenant;
use App\ProductStatus;
use App\ProductType;
use App\Support\TenantContext;
use Database\Seeders\DatabaseSeeder;

test('supplier admin can list tenant products', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    Product::factory()->forTenant($tenant)->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/products/index')
            ->has('products', 2));
});

test('supplier admin cannot see products from another tenant', function () {
    ['admin' => $admin] = createSupplierAdmin();

    Product::factory()->forTenant(Tenant::factory()->create())->create();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products', 0));
});

test('supplier admin can create a returnable jar product', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'name' => '25L Jar',
            'sku' => 'JAR-25L',
            'type' => ProductType::Jar->value,
            'capacity_liters' => 25,
            'unit_price' => 90,
            'deposit_amount' => 350,
            'is_returnable' => true,
            'status' => ProductStatus::Active->value,
        ])
        ->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('sku', 'JAR-25L')->first();

    expect($product)->not->toBeNull()
        ->and($product->tenant_id)->toBe($tenant->id)
        ->and($product->is_returnable)->toBeTrue()
        ->and($product->deposit_amount)->toBe('350.00');
});

test('supplier admin can quick update product prices', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $product = Product::factory()->forTenant($tenant)->create([
        'unit_price' => 80,
        'deposit_amount' => 300,
    ]);

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->patch(route('admin.products.update-price', $product), [
            'unit_price' => 85,
            'deposit_amount' => 320,
        ])
        ->assertRedirect();

    $product->refresh();

    expect($product->unit_price)->toBe('85.00')
        ->and($product->deposit_amount)->toBe('320.00');
});

test('supplier admin can deactivate and activate a product', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();

    $product = Product::factory()->forTenant($tenant)->create([
        'status' => ProductStatus::Active,
    ]);

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.products.deactivate', $product))
        ->assertRedirect();

    expect($product->fresh()->status)->toBe(ProductStatus::Inactive);

    $this->actingAs($admin)
        ->post(route('admin.products.activate', $product))
        ->assertRedirect();

    expect($product->fresh()->status)->toBe(ProductStatus::Active);
});

test('tenant id is never taken from product input', function () {
    ['tenant' => $tenant, 'admin' => $admin] = createSupplierAdmin();
    $otherTenant = Tenant::factory()->create();

    TenantContext::setId($tenant->id);

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'name' => 'Injected Product',
            'sku' => 'INJECTED',
            'type' => ProductType::Jar->value,
            'unit_price' => 50,
            'deposit_amount' => 100,
            'status' => ProductStatus::Active->value,
            'tenant_id' => $otherTenant->id,
        ])
        ->assertRedirect(route('admin.products.index'));

    expect(Product::query()->where('sku', 'INJECTED')->value('tenant_id'))
        ->toBe($tenant->id);
});

test('database seeder creates demo jar and jug products', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Product::query()->where('sku', 'JAR-20L')->exists())->toBeTrue()
        ->and(Product::query()->where('sku', 'JUG-15L')->exists())->toBeTrue()
        ->and(Product::query()->where('sku', 'JAR-20L')->value('is_returnable'))->toBeTrue()
        ->and(Product::query()->where('sku', 'JUG-15L')->value('capacity_liters'))->toBe('15.00');
});
