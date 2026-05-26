# SimpCommerce — Modular Monolith Architecture

> **Status**: Complete — All phases (0–8) implemented, Storefront API live
> **Branch**: `arch/modular-monolith` (active development)
> **Database**: PostgreSQL 16+ (SQLite in-memory for tests)
> **Tests**: 147 passing

**Repositories**:
- `simpcommerce-api` — Laravel 13 API backend (this repo)
- `simpcommerce-dashboard` — Vue 3 + TS SPA (staff dashboard, separate repo)
- `simpcommerce-storefront-*` — Nuxt 4 SSR storefronts (separate repos per store)

---

## 1. Motivation

The codebase was built as a straightforward monolithic Laravel app with a flat directory structure. The system now needs:

- **Multiple storefronts** — clothing, electronics, each with their own public Nuxt website
- **Multiple sales channels** — POS (in-store), online storefronts, future channels
- **Clearer domain boundaries** — developers need to modify specific business areas without touching unrelated code

A **Modular Monolith** gives clean separation within a single deployable unit — no microservices complexity, no network overhead, but the same disciplined boundaries you'd find in a distributed system.

---

## 2. Module Map

### 14 Modules

```
┌──────────────────────────────────────────────────────────────────┐
│                        SimpCommerce API                          │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │  Core    │  │ Identity │  │  Store   │  │ Catalog  │        │
│  │(Shared   │  │ (Auth,   │  │ (Multi-  │  │ (Products│        │
│  │ Kernel)  │  │  Users)  │  │  store)  │  │ & Categ) │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│                                                                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ Customer │  │  Sales   │  │Inventory │  │Promotion │        │
│  │ (CRM,    │  │ (Orders, │  │ (Stock,  │  │(Discounts│        │
│  │  Cart)   │  │  POS)    │  │ Movement)│  │ & Rules) │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│                                                                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ Supplier │  │   Cash   │  │  Audit   │  │  Report  │        │
│  │ (Vendors)│  │  (Sessions│  │  (Logs)  │  │ (Analytics        │
│  │          │  │  & Reg.) │  │          │  │  & Dashboard)     │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│                                                                  │
│  ┌──────────┐   ┌───────────────────────────────────────────┐   │
│  │  System  │   │             ECommerce Module              │   │
│  │ (Backup) │   │ (Cart, Checkout, Shipments, Storefront API)│   │
│  └──────────┘   └───────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Key Architectural Features

### Multi-Store (Tenant Per Store)
- `store_id` as nullable FK on 6 tables: `products`, `categories`, `orders`, `discounts`, `suppliers`, `cash_sessions`
- **ResolveStore middleware** reads `X-Store` header, resolves `app('current_store')`
- Middleware registered as `store` alias, applied to `/api/storefront/*` route group
- No global scopes — explicit `->where('store_id', ...)` in queries
- Store model extended with `domain`, `logo`, `phone`, `email` fields

### API Route Architecture
- Master `routes/api.php` (~22 lines) loads 15 per-module route files
- Middleware groups: Public → Storefront → Customer → Staff → Admin
- Storefront group (`/api/storefront/*`) is public + store-scoped
- Customer portal (`/api/cart|checkout|addresses|my/*`) requires `auth:customer`

### ECommerce Module
- Server-side cart with stock validation, tied to authenticated customers
- COD checkout via `OnlineOrderService` (transactional: order + invoice + shipment + stock deduction)
- Order lifecycle: `processing → shipped → delivered` (forward) / `processing → cancelled` (restock)
- `source` field on orders: `pos` or `online`

### Database
- **PostgreSQL 16+** for development/production
- **SQLite in-memory** for tests (configured in phpunit.xml)
- Sequential number generators: `INV-{YYYYMMDD}-{XXXX}`, `ORD-{YYYYMMDD}-{XXXX}`

### Backup System
- Driver-aware backup controller: `pg_dump` for PostgreSQL, `mysqldump` for MySQL, file copy for SQLite
- Filename uses driver extension, listing filters by `backup-` prefix

---

## 4. Multi-Store Data Model

### The `stores` Table

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | Auto-increment |
| `name` | VARCHAR(255) | Store display name |
| `slug` | VARCHAR(255) UNIQUE | Identifier sent as `X-Store` header |
| `domain` | VARCHAR(255) NULL | Custom domain for storefront |
| `description` | TEXT NULL | Brand description |
| `logo` | VARCHAR(255) NULL | Logo path |
| `phone` | VARCHAR(255) NULL | Contact phone |
| `email` | VARCHAR(255) NULL | Contact email |
| `is_active` | BOOLEAN | Default `true` |
| `settings` | JSON NULL | Freeform store config (currency, theme, shipping) |

### Store Resolution

```
Nuxt storefront → NUXT_PUBLIC_STORE_SLUG=clothing
                → X-Store: clothing header on every request
                → ResolveStore middleware reads header
                → Store::where('slug', $slug)->firstOrFail()
                → app('current_store') = resolved Store model
```

### Tables with `store_id`

| Table | Nullable | Scoped in Storefront? |
|---|---|---|
| `products` | Yes | ✅ |
| `categories` | Yes | ✅ |
| `orders` | Yes | Set at checkout |
| `discounts` | Yes | ⏳ Not yet |
| `suppliers` | Yes | ⏳ Not yet |
| `cash_sessions` | Yes | Staff only |

---

## 5. API Routes

### Route Groups

| Group | Prefix | Middleware | Purpose |
|---|---|---|---|
| Public | `/api/auth/*`, `/api/customer/register\|login` | throttle | Login endpoints |
| Storefront | `/api/storefront/*` | `store`, throttle | Public catalog browsing, store settings |
| Customer | `/api/cart\|checkout\|addresses\|my\|customer/*` | `auth:customer`, throttle | Customer portal |
| Staff | `/api/*` (catalog, sales, etc.) | `auth:sanctum`, throttle | Dashboard CRUD |
| Admin | (staff +) | `admin` | User management, audit, backups |

### Storefront Public Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/storefront/products?page=&category_id=&search=` | Paginated product listing |
| GET | `/api/storefront/products/{slug}` | Product detail with variants |
| GET | `/api/storefront/categories` | Category list with product counts |
| GET | `/api/storefront/settings` | Store config (name, logo, currency) |

---

## 6. Directory Structure

```
simpcommerce-api/
├── app/
│   └── Modules/
│       ├── Core/              # ApiResponse, QueryFilter, Enums (InvoiceStatus, OrderStatus, PaymentMethod)
│       ├── Identity/          # AuthController, UserController, ProfileController, AdminMiddleware, User model
│       ├── Store/             # StoreController, ResolveStore middleware, Store model (with domain, logo, phone, email)
│       ├── Catalog/           # ProductController, ProductVariantController, CategoryController, StorefrontController
│       │                      # ProductImportService, ProductExportService, MediaService
│       ├── Customer/          # CustomerController, CustomerAuthController, CustomerProfileController, AddressController
│       │                      # Customer model (Authenticatable + HasApiTokens), Address model
│       ├── Sales/             # OrderController, InvoiceController, OrderService, InvoiceNumberGenerator
│       │                      # Order, OrderItem, Payment, Invoice models
│       ├── Inventory/         # StockMovementController, StockService, StockMovement model
│       ├── Promotion/         # DiscountController, DiscountService, Discount model
│       ├── Supplier/          # SupplierController, Supplier model
│       ├── Cash/              # CashSessionController, CashSession model
│       ├── Audit/             # AuditLogController, AuditLog model
│       ├── Report/            # DashboardController, ReportController
│       ├── System/            # BackupController (driver-aware: pg_dump/mysqldump/copy)
│       └── ECommerce/         # CartController, CheckoutController, MyOrderController, OnlineOrderService
│                              # CartItem, Shipment models
├── database/
│   ├── factories/             # 11 model factories (including StoreFactory)
│   ├── migrations/            # 26 migration files
│   └── seeders/               # DatabaseSeeder (assigns store_id to seeded data)
├── routes/
│   ├── api.php                # Master route loader (~22 lines)
│   └── modules/               # 15 per-module route files (including storefront.php)
└── tests/                     # 20 test files, 147 tests
```

---

## 7. Current Status

### ✅ Completed

- [x] **14 modules** fully migrated and operational
- [x] **Multi-store**: `store_id` on 6 tables, `ResolveStore` middleware wired, Store model extended
- [x] **Storefront API**: `/api/storefront/products|categories|settings` public endpoints live
- [x] **ECommerce**: Cart, COD checkout, shipments, customer orders all implemented
- [x] **PostgreSQL**: Primary database, SQLite for tests
- [x] **Backup**: Driver-aware (`pg_dump`/`mysqldump`/copy)
- [x] **147 tests**: All passing

### ⏳ Next Steps

1. **Admin dashboard**: Integrate online order management (source filter, mark shipped/delivered actions)
2. **Storefront auth**: Switch to Sanctum HttpOnly cookie auth for storefronts
3. **Payment gateways**: KBZ Pay / Wave Money (deferred)
4. **Plain products**: Support products without variants (deferred)
5. **OAuth (Google)**: Customer social login (deferred)
