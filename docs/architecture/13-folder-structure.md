# Folder Structure

Jalwala follows a domain-oriented folder structure within a single Laravel codebase. Backend code is grouped by domain; frontend pages mirror the same boundaries.

---

## Backend (`app/`)

```
app/
├── Actions/
│   ├── Customer/
│   ├── Order/
│   ├── Subscription/
│   └── Wallet/
├── DTOs/
│   ├── Customer/
│   ├── Order/
│   └── ...
├── Enums/
│   ├── OrderStatus.php
│   ├── WalletTransactionCategory.php
│   └── ...
├── Events/
│   ├── Customer/
│   ├── Order/
│   └── ...
├── Exceptions/
│   └── Domain/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # Supplier Admin
│   │   ├── Agent/           # Delivery Agent (mobile)
│   │   ├── Portal/          # Customer portal
│   │   └── Platform/        # Super Admin (Phase 10)
│   ├── Middleware/
│   │   ├── EnsureTenantIsSet.php
│   │   └── ...
│   └── Requests/
│       ├── Customer/
│       └── ...
├── Jobs/
│   ├── Subscription/
│   └── Reports/
├── Listeners/
│   ├── Order/
│   └── ...
├── Models/
│   ├── Concerns/
│   │   └── BelongsToTenant.php
│   └── Scopes/
│       └── TenantScope.php
├── Notifications/
├── Policies/
├── Providers/
├── Services/
│   ├── Customer/
│   ├── Deposit/
│   ├── Delivery/
│   ├── Inventory/
│   ├── Order/
│   ├── Report/
│   ├── Subscription/
│   ├── Tenant/
│   ├── User/
│   └── Wallet/
├── Support/
│   └── TenantContext.php
└── Traits/
    └── BelongsToTenant.php
```

---

## Database (`database/`)

```
database/
├── factories/
├── migrations/
└── seeders/
    ├── RolesAndPermissionsSeeder.php
    └── DemoTenantSeeder.php
```

---

## Frontend (`resources/js/`)

```
resources/js/
├── pages/
│   ├── admin/
│   ├── agent/
│   ├── portal/
│   └── platform/
├── components/
│   ├── admin/
│   ├── agent/
│   ├── portal/
│   └── ui/
└── layouts/
    ├── admin-layout.tsx
    ├── agent-layout.tsx    # Bottom nav
    └── portal-layout.tsx   # Bottom nav
```

### Frontend Conventions

| Directory | Purpose |
|-----------|---------|
| `pages/` | Inertia pages grouped by role/domain |
| `components/` | Shared UI + domain-specific components |
| `layouts/` | Role-based layouts (admin sidebar, agent/portal bottom nav) |
| `hooks/` | `useTenant`, `usePermissions`, `useMobileNav` |
| `types/` | TypeScript domain types mirroring backend DTOs |
| `lib/` | Utils, formatters (currency, dates) |

---

## Routes (`routes/`)

```
routes/
├── web.php
├── admin.php
├── agent.php
├── portal.php
└── platform.php
```

| File | Prefix | Audience |
|------|--------|----------|
| `admin.php` | `/admin` | Supplier Admin |
| `agent.php` | `/agent` | Delivery Agent |
| `portal.php` | `/portal` | Customer |
| `platform.php` | `/platform` | Super Admin (Phase 10) |

---

## Tests (`tests/`)

```
tests/
├── Feature/
│   ├── Customer/
│   ├── Order/
│   └── ...
└── Unit/
    └── Services/
```

### Testing Conventions

- Feature tests per domain (Pest)
- Policy tests for authorization
- Tenant scoping tests on all tenant-scoped models
- Service unit tests for business logic

---

## Documentation (`docs/`)

```
docs/
└── architecture/
    ├── 01-system-architecture.md
    ├── 02-domain-design.md
    ├── ...
    ├── erd.md
    └── permissions-matrix.md
```

---

## Layer Responsibilities

| Layer | Location | Responsibility |
|-------|----------|----------------|
| Controllers | `Http/Controllers/{Role}/` | Thin: validate, authorize, delegate, respond |
| Actions | `Actions/{Domain}/` | Single-purpose orchestration |
| Services | `Services/{Domain}/` | Business logic, transactions |
| DTOs | `DTOs/{Domain}/` | Typed contracts between layers |
| Models | `Models/` | Eloquent entities, relationships, scopes |
| Policies | `Policies/` | Record-level authorization |
| Events | `Events/{Domain}/` | Domain events (past tense) |
| Listeners | `Listeners/{Domain}/` | Side effects (sync or dispatch jobs) |
| Jobs | `Jobs/{Domain}/` | Async work with tenant context |
