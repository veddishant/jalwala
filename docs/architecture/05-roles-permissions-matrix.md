# Roles & Permissions Matrix

Authorization uses **Spatie Permission** (coarse checks) combined with **Laravel Policies** (record-level scoping). See [permissions-matrix.md](./permissions-matrix.md) for the full exportable reference table.

---

## Roles

| Role | Scope | Description |
|------|-------|-------------|
| `super-admin` | Platform | Manages tenants (Phase 10), bypasses tenant scope |
| `supplier-admin` | Tenant | Full tenant operations |
| `delivery-agent` | Tenant | Assigned deliveries only |
| `customer` | Tenant | Own data via portal |

---

## Permission Groups

### Platform (Phase 10)

- `platform.tenants.view`
- `platform.tenants.create`
- `platform.tenants.update`
- `platform.tenants.suspend`

### Users

- `users.view`
- `users.create`
- `users.update`
- `users.deactivate`
- `users.assign-roles`

### Customers

- `customers.view`
- `customers.create`
- `customers.update`
- `customers.close`
- `customers.pause`
- `customers.addresses.manage`

### Products

- `products.view`
- `products.create`
- `products.update`
- `products.deactivate`

### Wallet

- `wallet.view`
- `wallet.top-up`
- `wallet.adjust`
- `wallet.view-ledger`

### Deposits

- `deposits.view`
- `deposits.collect`
- `deposits.refund`
- `deposits.adjust`
- `deposits.view-ledger`

### Subscriptions

- `subscriptions.view`
- `subscriptions.create`
- `subscriptions.update`
- `subscriptions.pause`
- `subscriptions.resume`
- `subscriptions.cancel`

### Orders

- `orders.view`
- `orders.create`
- `orders.update`
- `orders.cancel`
- `orders.confirm`

### Deliveries

- `deliveries.view`
- `deliveries.assign`
- `deliveries.update-status`
- `deliveries.view-own` (agent scoped)

### Inventory

- `inventory.view`
- `inventory.adjust`
- `inventory.view-customer`

### Reports

- `reports.sales`
- `reports.consumption`
- `reports.wallet`
- `reports.deposits`
- `reports.outstanding`
- `reports.agent-performance`

### Settings

- `settings.tenant.view`
- `settings.tenant.update`

---

## Role → Permission Mapping (Summary)

| Permission | Super Admin | Supplier Admin | Delivery Agent | Customer |
|------------|:-----------:|:--------------:|:--------------:|:--------:|
| platform.* | ✓ | — | — | — |
| users.* | ✓ | ✓ | — | — |
| customers.view | ✓ | ✓ | own route | own |
| customers.create/update/close | ✓ | ✓ | — | — |
| products.view | ✓ | ✓ | ✓ | ✓ |
| products.create/update | ✓ | ✓ | — | — |
| wallet.view | ✓ | ✓ | — | own |
| wallet.top-up/adjust | ✓ | ✓ | — | — |
| deposits.* | ✓ | ✓ | — | view own |
| subscriptions.* | ✓ | ✓ | — | view/pause own |
| orders.view | ✓ | ✓ | assigned | own |
| orders.create | ✓ | ✓ | — | own portal |
| orders.cancel | ✓ | ✓ | — | own pending |
| deliveries.view | ✓ | ✓ | — | — |
| deliveries.assign | ✓ | ✓ | — | — |
| deliveries.update-status | ✓ | ✓ | own | — |
| deliveries.view-own | — | — | ✓ | — |
| inventory.* | ✓ | ✓ | view | — |
| reports.* | ✓ | ✓ | — | — |
| settings.tenant.* | ✓ | ✓ | — | — |

---

## Implementation Notes

- Seed all permissions and role mappings in `RolesAndPermissionsSeeder`
- Policies enforce record-level scoping for `customer` and `delivery-agent` roles
- Middleware: `permission:orders.create` or `role:supplier-admin` on route groups
- Customer and delivery-agent "own" access is enforced in policies, not Spatie alone

### Two-Layer Authorization

1. **Spatie Permission** — `$user->can('orders.assign')` at route/controller level
2. **Laravel Policies** — `$this->authorize('update', $order)` for tenant-aware, record-level checks

### Super Admin

Users with `tenant_id = null` and `super-admin` role skip tenant scope but still require explicit permissions for destructive platform actions.
