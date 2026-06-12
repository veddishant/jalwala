<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'platform.tenants.view',
        'platform.tenants.create',
        'platform.tenants.update',
        'platform.tenants.suspend',
        'users.view',
        'users.create',
        'users.update',
        'users.deactivate',
        'users.assign-roles',
        'customers.view',
        'customers.create',
        'customers.update',
        'customers.close',
        'customers.pause',
        'customers.addresses.manage',
        'products.view',
        'products.create',
        'products.update',
        'products.deactivate',
        'wallet.view',
        'wallet.top-up',
        'wallet.adjust',
        'wallet.view-ledger',
        'deposits.view',
        'deposits.collect',
        'deposits.refund',
        'deposits.adjust',
        'deposits.view-ledger',
        'subscriptions.view',
        'subscriptions.create',
        'subscriptions.update',
        'subscriptions.pause',
        'subscriptions.resume',
        'subscriptions.cancel',
        'orders.view',
        'orders.create',
        'orders.update',
        'orders.cancel',
        'orders.confirm',
        'deliveries.view',
        'deliveries.assign',
        'deliveries.update-status',
        'deliveries.view-own',
        'inventory.view',
        'inventory.adjust',
        'inventory.view-customer',
        'reports.sales',
        'reports.consumption',
        'reports.wallet',
        'reports.deposits',
        'reports.outstanding',
        'reports.agent-performance',
        'settings.tenant.view',
        'settings.tenant.update',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $allPermissions = Permission::query()->pluck('name')->all();

        $superAdmin = Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($allPermissions);

        $supplierAdmin = Role::query()->firstOrCreate(['name' => 'supplier-admin', 'guard_name' => 'web']);
        $supplierAdmin->syncPermissions(
            collect($allPermissions)
                ->reject(fn (string $permission): bool => str_starts_with($permission, 'platform.'))
                ->values()
                ->all()
        );

        $deliveryAgent = Role::query()->firstOrCreate(['name' => 'delivery-agent', 'guard_name' => 'web']);
        $deliveryAgent->syncPermissions([
            'products.view',
            'orders.view',
            'deliveries.view-own',
            'deliveries.update-status',
            'inventory.view-customer',
        ]);

        $customer = Role::query()->firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $customer->syncPermissions([
            'customers.view',
            'products.view',
            'wallet.view',
            'deposits.view',
            'subscriptions.view',
            'subscriptions.pause',
            'orders.view',
            'orders.create',
            'orders.cancel',
        ]);
    }
}
