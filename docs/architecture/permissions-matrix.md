# Permissions Matrix

Exportable reference table for all Jalwala roles and permissions. Use this for seeder implementation and access control audits.

See also [05-roles-permissions-matrix.md](./05-roles-permissions-matrix.md) for implementation notes.

---

## Roles

| Role | Scope | Description |
|------|-------|-------------|
| `super-admin` | Platform | Manages tenants (Phase 10), bypasses tenant scope |
| `supplier-admin` | Tenant | Full tenant operations |
| `delivery-agent` | Tenant | Assigned deliveries only |
| `customer` | Tenant | Own data via portal |

---

## Full Permission List

### Platform (Phase 10)

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `platform.tenants.view` | ✓ | — | — | — |
| `platform.tenants.create` | ✓ | — | — | — |
| `platform.tenants.update` | ✓ | — | — | — |
| `platform.tenants.suspend` | ✓ | — | — | — |

### Users

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `users.view` | ✓ | ✓ | — | — |
| `users.create` | ✓ | ✓ | — | — |
| `users.update` | ✓ | ✓ | — | — |
| `users.deactivate` | ✓ | ✓ | — | — |
| `users.assign-roles` | ✓ | ✓ | — | — |

### Customers

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `customers.view` | ✓ | ✓ | — | own |
| `customers.create` | ✓ | ✓ | — | — |
| `customers.update` | ✓ | ✓ | — | — |
| `customers.close` | ✓ | ✓ | — | — |
| `customers.pause` | ✓ | ✓ | — | — |
| `customers.addresses.manage` | ✓ | ✓ | — | own |

### Products

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `products.view` | ✓ | ✓ | ✓ | ✓ |
| `products.create` | ✓ | ✓ | — | — |
| `products.update` | ✓ | ✓ | — | — |
| `products.deactivate` | ✓ | ✓ | — | — |

### Wallet

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `wallet.view` | ✓ | ✓ | — | own |
| `wallet.top-up` | ✓ | ✓ | — | — |
| `wallet.adjust` | ✓ | ✓ | — | — |
| `wallet.view-ledger` | ✓ | ✓ | — | own |

### Deposits

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `deposits.view` | ✓ | ✓ | — | own |
| `deposits.collect` | ✓ | ✓ | — | — |
| `deposits.refund` | ✓ | ✓ | — | — |
| `deposits.adjust` | ✓ | ✓ | — | — |
| `deposits.view-ledger` | ✓ | ✓ | — | own |

### Subscriptions

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `subscriptions.view` | ✓ | ✓ | — | own |
| `subscriptions.create` | ✓ | ✓ | — | — |
| `subscriptions.update` | ✓ | ✓ | — | — |
| `subscriptions.pause` | ✓ | ✓ | — | own |
| `subscriptions.resume` | ✓ | ✓ | — | own |
| `subscriptions.cancel` | ✓ | ✓ | — | — |

### Orders

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `orders.view` | ✓ | ✓ | assigned | own |
| `orders.create` | ✓ | ✓ | — | own |
| `orders.update` | ✓ | ✓ | — | — |
| `orders.cancel` | ✓ | ✓ | — | own pending |
| `orders.confirm` | ✓ | ✓ | — | — |

### Deliveries

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `deliveries.view` | ✓ | ✓ | — | — |
| `deliveries.assign` | ✓ | ✓ | — | — |
| `deliveries.update-status` | ✓ | ✓ | own | — |
| `deliveries.view-own` | — | — | ✓ | — |

### Inventory

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `inventory.view` | ✓ | ✓ | view | — |
| `inventory.adjust` | ✓ | ✓ | — | — |
| `inventory.view-customer` | ✓ | ✓ | view | — |

### Reports

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `reports.sales` | ✓ | ✓ | — | — |
| `reports.consumption` | ✓ | ✓ | — | — |
| `reports.wallet` | ✓ | ✓ | — | — |
| `reports.deposits` | ✓ | ✓ | — | — |
| `reports.outstanding` | ✓ | ✓ | — | — |
| `reports.agent-performance` | ✓ | ✓ | — | — |

### Settings

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| `settings.tenant.view` | ✓ | ✓ | — | — |
| `settings.tenant.update` | ✓ | ✓ | — | — |

---

## Legend

| Symbol | Meaning |
|--------|---------|
| ✓ | Permission granted via Spatie role |
| — | Permission not granted |
| own | Access limited to own records via Laravel Policy |
| own pending | Customer can cancel only their own pending orders |
| assigned | Delivery Agent sees orders assigned to them |
| view | Read-only access (no mutations) |

---

## Role Permission Counts

| Role | Direct Permissions | Policy-Scoped |
|------|-------------------|---------------|
| `super-admin` | All platform + tenant permissions | Bypasses tenant scope |
| `supplier-admin` | All tenant permissions | Full tenant access |
| `delivery-agent` | Deliveries, limited views | Own assignments only |
| `customer` | Portal subset | Own records only |

---

## Seeder Reference

All permissions and role mappings should be seeded in `RolesAndPermissionsSeeder`:

```php
// Permission groups to seed:
// platform.*, users.*, customers.*, products.*, wallet.*,
// deposits.*, subscriptions.*, orders.*, deliveries.*,
// inventory.*, reports.*, settings.tenant.*
```

Policies enforce record-level scoping for `customer` and `delivery-agent` roles beyond what Spatie permissions alone provide.
