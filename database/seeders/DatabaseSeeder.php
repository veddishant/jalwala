<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
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
    }
}
