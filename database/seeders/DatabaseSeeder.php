<?php

namespace Database\Seeders;

use App\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\ProductStatus;
use App\ProductType;
use App\TenantStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $tenant = Tenant::query()->create([
            'name' => 'Demo Water Supply',
            'slug' => 'demo-water-supply',
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'settings' => [],
            'status' => TenantStatus::Active,
        ]);

        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'jalwala@yopmail.com',
            'password' => Hash::make('123456'),
        ]);
        $superAdmin->assignRole('super-admin');

        $supplierAdmin = User::factory()->forTenant($tenant)->create([
            'name' => 'Supplier Admin',
            'email' => 'admin@demo-water-supply.test',
            'password' => Hash::make('123456'),
        ]);
        $supplierAdmin->assignRole('supplier-admin');

        $deliveryAgent = User::factory()->forTenant($tenant)->create([
            'name' => 'Delivery Agent',
            'email' => 'agent@demo-water-supply.test',
            'password' => Hash::make('123456'),
        ]);
        $deliveryAgent->assignRole('delivery-agent');

        $customerUser = User::factory()->forTenant($tenant)->create([
            'name' => 'Customer User',
            'email' => 'customer@demo-water-supply.test',
            'password' => Hash::make('123456'),
        ]);
        $customerUser->assignRole('customer');

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $customerUser->id,
            'code' => 'CUST-0001',
            'name' => 'Customer User',
            'phone' => '9876543210',
            'email' => 'customer@demo-water-supply.test',
            'status' => CustomerStatus::Active,
        ]);

        CustomerAddress::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'label' => 'Home',
            'address_line_1' => '12 MG Road',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'is_default' => true,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => '20L Jar',
            'sku' => 'JAR-20L',
            'type' => ProductType::Jar,
            'capacity_liters' => 20,
            'unit_price' => 25,
            'deposit_amount' => 300,
            'is_returnable' => true,
            'status' => ProductStatus::Active,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => '15L Jug',
            'sku' => 'JUG-15L',
            'type' => ProductType::Jar,
            'capacity_liters' => 15,
            'unit_price' => 50,
            'deposit_amount' => 500,
            'is_returnable' => true,
            'status' => ProductStatus::Active,
        ]);
    }
}
