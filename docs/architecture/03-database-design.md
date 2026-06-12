# Database Design

## Design Decisions

- **UUIDs vs big integers:** Use `bigint` PKs for performance; `uuid` public references on orders/invoices for external sharing
- **Soft deletes:** `customers`, `products`, `users` — not on financial ledger tables
- **Money:** `decimal(12,2)` stored; never float. Currency per tenant (default INR)
- **Timestamps:** All tables `created_at`, `updated_at`; ledger tables also `created_by` (user_id)
- **tenant_id:** Required on all tenant-scoped tables; indexed on every table
- **Audit:** Financial tables are append-only (no UPDATE/DELETE); corrections via reversing transactions

See [erd.md](./erd.md) for the full entity-relationship diagram.

---

## Core Tables

### `tenants`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | Business name |
| slug | varchar(100) UNIQUE | URL-safe identifier |
| timezone | varchar(50) | Default Asia/Kolkata |
| currency | char(3) | Default INR |
| settings | jsonb | Branding, notification prefs, order lead time |
| status | enum | active, suspended, closed |
| created_at, updated_at | timestamp | |

**Indexes:** `slug` UNIQUE, `status`

---

### `users` (extend existing)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK nullable | NULL = platform Super Admin |
| name | varchar(255) | |
| email | varchar(255) | UNIQUE per tenant (composite unique) |
| phone | varchar(20) nullable | |
| password | varchar(255) | |
| status | enum | active, inactive |
| email_verified_at | timestamp nullable | |
| ... | | Existing 2FA/passkey columns |

**Indexes:** `(tenant_id, email)` UNIQUE, `tenant_id`, `status`

---

### `customers`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| user_id | bigint FK nullable | Portal login link |
| code | varchar(50) | Human-readable customer code |
| name | varchar(255) | |
| phone | varchar(20) | |
| email | varchar(255) nullable | |
| status | enum | prospect, active, paused, closed |
| closed_at | timestamp nullable | |
| closure_reason | text nullable | |
| notes | text nullable | |
| created_at, updated_at, deleted_at | | |

**Indexes:** `(tenant_id, code)` UNIQUE, `(tenant_id, phone)`, `user_id`, `status`

---

### `customer_addresses`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| customer_id | bigint FK | |
| label | varchar(50) | Home, Office |
| address_line_1 | varchar(255) | |
| address_line_2 | varchar(255) nullable | |
| city | varchar(100) | |
| state | varchar(100) | |
| postal_code | varchar(20) | |
| latitude | decimal(10,7) nullable | Future routing |
| longitude | decimal(10,7) nullable | |
| is_default | boolean | |
| delivery_instructions | text nullable | |

**Indexes:** `(tenant_id, customer_id)`, `(tenant_id, customer_id, is_default)`

---

### `products`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| name | varchar(255) | 20L Jar |
| sku | varchar(50) | |
| type | enum | jar, bottle, accessory |
| capacity_liters | decimal(8,2) nullable | |
| unit_price | decimal(12,2) | Per delivery unit |
| deposit_amount | decimal(12,2) | Per unit jar deposit |
| is_returnable | boolean | Jars yes, bottles maybe not |
| status | enum | active, inactive |
| created_at, updated_at, deleted_at | | |

**Indexes:** `(tenant_id, sku)` UNIQUE, `tenant_id, status`

---

### `wallets`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| customer_id | bigint FK UNIQUE | One wallet per customer |
| balance | decimal(12,2) | Cached running balance |
| low_balance_threshold | decimal(12,2) nullable | Alert trigger |
| created_at, updated_at | | |

**Indexes:** `(tenant_id, customer_id)` UNIQUE

---

### `wallet_transactions` (append-only ledger)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| wallet_id | bigint FK | |
| type | enum | credit, debit |
| category | enum | top_up, order_payment, refund, adjustment, opening_balance |
| amount | decimal(12,2) | Always positive |
| balance_after | decimal(12,2) | Snapshot after txn |
| reference_type | varchar(50) nullable | order, manual |
| reference_id | bigint nullable | Polymorphic-lite |
| idempotency_key | varchar(100) UNIQUE | Prevent duplicates |
| description | text nullable | |
| created_by | bigint FK nullable | user_id |
| created_at | timestamp | No updated_at |

**Indexes:** `(tenant_id, wallet_id, created_at)`, `idempotency_key` UNIQUE, `(reference_type, reference_id)`

---

### `customer_deposits`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| customer_id | bigint FK UNIQUE | |
| balance | decimal(12,2) | Total deposit held |
| created_at, updated_at | | |

---

### `deposit_transactions` (append-only)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| customer_deposit_id | bigint FK | |
| type | enum | collect, refund, adjustment |
| amount | decimal(12,2) | |
| balance_after | decimal(12,2) | |
| jar_count | integer | Units affected |
| product_id | bigint FK nullable | |
| reference_type | varchar(50) nullable | |
| reference_id | bigint nullable | |
| description | text nullable | |
| created_by | bigint FK nullable | |
| created_at | timestamp | |

**Indexes:** `(tenant_id, customer_deposit_id, created_at)`

---

### `subscriptions`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| customer_id | bigint FK | |
| customer_address_id | bigint FK | Delivery address |
| status | enum | active, paused, cancelled |
| start_date | date | |
| end_date | date nullable | |
| paused_until | date nullable | Quick pause marker |
| notes | text nullable | |
| created_at, updated_at | | |

---

### `subscription_items`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| subscription_id | bigint FK | |
| product_id | bigint FK | |
| quantity | integer | |
| unit_price | decimal(12,2) | Snapshot at subscription time |
| created_at, updated_at | | |

**Indexes:** `(tenant_id, subscription_id)`

---

### `subscription_schedules`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| subscription_id | bigint FK | |
| day_of_week | tinyint | 0=Sun … 6=Sat |
| created_at, updated_at | | |

**Indexes:** `(tenant_id, subscription_id, day_of_week)` UNIQUE

---

### `subscription_pauses`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| subscription_id | bigint FK | |
| start_date | date | |
| end_date | date | Vacation range |
| reason | varchar(255) nullable | |
| created_by | bigint FK | |
| created_at, updated_at | | |

---

### `orders`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| uuid | uuid UNIQUE | Public reference |
| customer_id | bigint FK | |
| customer_address_id | bigint FK | |
| subscription_id | bigint FK nullable | NULL = manual order |
| source | enum | manual, subscription, customer_portal |
| status | enum | See [order lifecycle](./07-order-lifecycle.md) |
| subtotal | decimal(12,2) | |
| total | decimal(12,2) | |
| wallet_amount_charged | decimal(12,2) default 0 | |
| scheduled_date | date | Delivery date |
| delivered_at | timestamp nullable | |
| cancelled_at | timestamp nullable | |
| cancellation_reason | text nullable | |
| notes | text nullable | |
| created_by | bigint FK nullable | |
| created_at, updated_at | | |

**Indexes:** `(tenant_id, status, scheduled_date)`, `(tenant_id, customer_id)`, `uuid` UNIQUE, `subscription_id`

---

### `order_items`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| order_id | bigint FK | |
| product_id | bigint FK | |
| quantity | integer | |
| unit_price | decimal(12,2) | Snapshot |
| line_total | decimal(12,2) | |

---

### `order_status_histories`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| order_id | bigint FK | |
| from_status | varchar(50) nullable | |
| to_status | varchar(50) | |
| changed_by | bigint FK nullable | |
| notes | text nullable | |
| created_at | timestamp | |

---

### `deliveries`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| order_id | bigint FK UNIQUE | One delivery per order |
| delivery_agent_id | bigint FK | users.id |
| status | enum | assigned, in_progress, completed, failed |
| assigned_at | timestamp | |
| started_at | timestamp nullable | |
| completed_at | timestamp nullable | |
| failure_reason | text nullable | |
| notes | text nullable | |
| created_at, updated_at | | |

**Indexes:** `(tenant_id, delivery_agent_id, status)`, `(tenant_id, status)`

---

### `inventory_locations`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| locatable_type | varchar(50) | tenant_warehouse, customer |
| locatable_id | bigint | tenant_id or customer_id |
| name | varchar(255) | |
| created_at, updated_at | | |

**Indexes:** `(tenant_id, locatable_type, locatable_id)` UNIQUE

---

### `inventory_balances`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| inventory_location_id | bigint FK | |
| product_id | bigint FK | |
| filled_quantity | integer default 0 | |
| empty_quantity | integer default 0 | |
| updated_at | timestamp | |

**Indexes:** `(tenant_id, inventory_location_id, product_id)` UNIQUE

---

### `inventory_movements`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK | |
| inventory_location_id | bigint FK | |
| product_id | bigint FK | |
| movement_type | enum | filled_in, filled_out, empty_in, empty_out, adjustment |
| quantity | integer | |
| reference_type | varchar(50) nullable | order, manual |
| reference_id | bigint nullable | |
| notes | text nullable | |
| created_by | bigint FK nullable | |
| created_at | timestamp | |

---

## Spatie Permission Tables

Existing tables (no `team_id`):

- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`

Permissions are global; tenant isolation is enforced via `tenant_id` on users and policies.
