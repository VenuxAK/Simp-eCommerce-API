# SimpCommerce — Modular Monolith Architecture

> **Status**: Complete — All phases implemented, Storefront API live, ECommerce + Wishlist active
> **Branch**: `arch/modular-monolith` (active development)
> **Database**: PostgreSQL 16+ (SQLite in-memory for tests)
> **Tests**: 147 passing
> **Routes**: 103 registered routes across 15 module files

**Repositories**:
- `simpcommerce-api` — Laravel 13 API backend (this repo)
- `simpcommerce-dashboard` — Vue 3 + TS SPA (staff dashboard, separate repo)
- `simpcommerce-storefront-*` — Nuxt 4 SSR storefronts (separate repos per store)

---

## 1. Motivation

A **Modular Monolith** gives clean domain separation within a single deployable unit — no microservices complexity, no network overhead, but disciplined module boundaries.

Driving requirements:
- **Multiple storefronts** — clothing, electronics, each with their own public Nuxt website
- **Multiple sales channels** — POS (in-store), online storefronts, future channels
- **Clear domain boundaries** — developers modify specific business areas without touching unrelated code

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
│  │(Shared   │  │ (Auth,   │  │ (Multi-  │  │(Products │        │
│  │ Kernel)  │  │  Users)  │  │  store)  │  │ & Categ.)│        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│                                                                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ Customer │  │  Sales   │  │Inventory │  │Promotion │        │
│  │(CRM,Auth,│  │ (Orders, │  │ (Stock,  │  │(Discounts│        │
│  │  OAuth)  │  │  POS)    │  │Movements)│  │ & Rules) │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│                                                                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ Supplier │  │   Cash   │  │  Audit   │  │  Report  │        │
│  │(Vendors) │  │(Sessions)│  │  (Logs)  │  │(Analytics│        │
│  │          │  │          │  │          │  │ & Dash.) │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│                                                                  │
│  ┌──────────┐   ┌───────────────────────────────────────────┐   │
│  │  System  │   │            ECommerce Module               │   │
│  │ (Backup) │   │(Cart, Wishlist, Checkout, Shipments, MyOrders)│ │
│  └──────────┘   └───────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Key Architectural Features

### Core Shared Kernel (`app/Modules/Core/`)

The Core module contains all shared infrastructure used across modules:

| Component                    | Purpose                                                                |
|------------------------------|------------------------------------------------------------------------|
| `Enums/` (11 enums)          | Strongly-typed PHP enums for all domain values (UserRole, OrderStatus, etc.) |
| `Traits/ApiResponse`         | Standardized `{ data }` / `{ message }` JSON response helpers          |
| `Traits/QueryFilter`         | Reusable Eloquent scope for search/date/status filtering               |
| `Traits/StoreScope`          | Canonical `resolveStoreId()` for multi-tenant store resolution        |
| `Traits/AuthorizesOwnership` | Ownership guard for customer-owned resources (cart items, addresses)   |
| `Traits/HandlesPasswordUpdate` | Shared password hashing logic (used in Profile, User, Customer)     |
| `Repositories/Repository`   | Base repository class with common Eloquent query helpers               |

### Multi-Store (Tenant Per Store)

- `store_id` FK on 8 tables: `products`, `categories`, `orders`, `discounts`, `suppliers`, `cash_sessions`, `customers`, `users`
- **`ResolveStore` middleware** reads `X-Store` header, resolves `app('current_store')`
- Middleware registered as `store` alias, applied to `/api/storefront/*` and `/api/customer/*` route groups
- **`StoreScope` trait** provides the canonical `resolveStoreId()` helper; used by services to avoid repeated `app()` calls
- No global scopes — explicit `->where('store_id', ...)` in queries

### API Route Architecture

- Master `routes/api.php` loads **15 per-module route files**
- **4 middleware groups**: Public → Storefront → Customer → Staff/Admin
- Storefront group (`/api/storefront/*`): public + store-scoped
- Customer portal (`/api/cart|checkout|addresses|wishlist|my/*|customer/*`): `store + stateful + auth:customer`

### Service Layer

Each module has a dedicated `Services/` directory. Business logic is extracted from controllers into services:

| Service                  | Module      | Responsibility                                          |
|--------------------------|-------------|---------------------------------------------------------|
| `OrderService`           | Sales       | POS order creation, status transitions, returns, stock  |
| `InvoiceService`         | Sales       | Invoice creation and linking                            |
| `InvoiceNumberGenerator` | Sales       | DB-locked sequential ORD/INV number generation          |
| `OnlineOrderService`     | ECommerce   | Transactional COD checkout (order + stock + invoice + shipment) |
| `CartService`            | ECommerce   | Cart CRUD with stock validation                         |
| `WishlistService`        | ECommerce   | Wishlist toggle, listing, clearing                      |
| `MyOrderService`         | ECommerce   | Customer-facing order history and cancellation          |
| `ProductService`         | Catalog     | Product + variant create/update/delete orchestration    |
| `ProductImportService`   | Catalog     | CSV import with per-row validation                      |
| `ProductExportService`   | Catalog     | CSV export with headers                                 |
| `StorefrontService`      | Catalog     | Store-scoped public product/category/settings queries   |
| `MediaService`           | Catalog     | Image upload and storage management                     |
| `DashboardService`       | Report      | Dashboard summary aggregation                           |
| `ReportService`          | Report      | Sales, best-sellers, payment-method analytics           |

### Repository Pattern

The `Core` module defines a base `Repository` class. The ECommerce module uses repositories to isolate data access for frequently-used queries:

- `CartItemRepository` — cart queries scoped to customer
- `WishlistItemRepository` — wishlist queries with product loading
- `ShipmentRepository` — shipment creation at checkout

### ECommerce Module

- **Cart**: Server-side, stock-validated, tied to authenticated customers
- **Wishlist**: Toggle-based add/remove per authenticated customer
- **COD Checkout**: Via `OnlineOrderService` (transactional: order + invoice + shipment + stock deduction + cart clear)
- **Order lifecycle**: `processing → shipped → delivered` (forward) / `processing → cancelled` (restock)
- **`source`** field on orders: `pos` or `online` (backed by `OrderSource` enum)

### Database

- **PostgreSQL 16+** for development/production
- **SQLite in-memory** for tests (configured in `phpunit.xml`)
- Sequential number generators with DB-level locking: `INV-{YYYYMMDD}-{XXXX}`, `ORD-{YYYYMMDD}-{XXXX}`

### Backup System

Driver-aware backup controller: `pg_dump` for PostgreSQL, `mysqldump` for MySQL, file copy for SQLite. Filenames include driver extension. `basename()` prevents path traversal on downloads.

---

## 4. Multi-Store Data Model

### The `stores` Table

| Column        | Type            | Notes                                      |
|---------------|-----------------|--------------------------------------------|
| `id`          | BIGINT PK       | Auto-increment                             |
| `name`        | VARCHAR(255)    | Store display name                         |
| `slug`        | VARCHAR(255)    | UNIQUE — identifier sent as `X-Store` header |
| `domain`      | VARCHAR(255)    | NULL — Custom domain for storefront        |
| `description` | TEXT            | NULL                                       |
| `logo`        | VARCHAR(255)    | NULL                                       |
| `phone`       | VARCHAR(255)    | NULL                                       |
| `email`       | VARCHAR(255)    | NULL                                       |
| `is_active`   | BOOLEAN         | Default `true`                             |
| `settings`    | JSON            | NULL — Freeform store config (currency, theme, shipping) |

### Store Resolution

```
Nuxt storefront → NUXT_PUBLIC_STORE_SLUG=clothing
                → X-Store: clothing header on every request
                → ResolveStore middleware reads header
                → Store::where('slug', $slug)->firstOrFail()
                → app('current_store') = resolved Store model
                → StoreScope::resolveStoreId() = store.id
```

### Tables with `store_id`

| Table           | Nullable | Scoped in Storefront?      |
|-----------------|----------|----------------------------|
| `products`      | Yes      | ✅                          |
| `categories`    | Yes      | ✅                          |
| `orders`        | Yes      | ✅ Set at checkout          |
| `customers`     | Yes      | ✅ Set at registration      |
| `discounts`     | Yes      | ⏳ Not yet                  |
| `suppliers`     | Yes      | ⏳ Not yet                  |
| `cash_sessions` | Yes      | Staff only                  |
| `users`         | FK       | Staff assignment only       |

---

## 5. Enums

All domain string values are backed by PHP 8.1+ backed enums in `app/Modules/Core/Enums/`:

```
AddressType.php          → Shipping, Billing
AuditAction.php          → Created, Updated, Deleted
DiscountScope.php        → All, Category, Product
DiscountType.php         → Percentage, Fixed
InvoiceStatus.php        → Draft, Issued, Paid, Cancelled, Overdue
OrderSource.php          → Pos, Online
OrderStatus.php          → Pending, Processing, Completed, Shipped, Delivered, Cancelled, Refunded
PaymentMethod.php        → Cash, Transfer
ShipmentMethod.php       → Cod, Standard, Express
StockMovementReason.php  → Sale, Purchase, Adjustment, Return
UserRole.php             → Root, StoreAdmin, Staff
```

---

## 6. API Routes

### Route Groups

| Group      | Prefix / Middleware                        | Purpose                          |
|------------|--------------------------------------------|----------------------------------|
| Public     | `throttle:10,1`                            | Login, register, OAuth           |
| Storefront | `store`, `throttle:60,1` + `/storefront/*` | Public catalog browsing          |
| Customer   | `store`, `stateful`, `auth:customer`, `throttle:60,1` | Customer portal     |
| Staff      | `store`, `auth:sanctum`, `throttle:60,1`   | Dashboard CRUD                   |
| Admin      | Staff + `admin` middleware                 | User management, audit, backups  |

### Storefront Public Endpoints

| Method | Endpoint                                           | Description                    |
|--------|----------------------------------------------------|--------------------------------|
| GET    | `/api/storefront/products?page=&category_id=&search=` | Paginated product listing   |
| GET    | `/api/storefront/products/{slug}`                  | Product detail with variants   |
| GET    | `/api/storefront/categories`                       | Category list with counts      |
| GET    | `/api/storefront/settings`                         | Store config                   |

---

## 7. Directory Structure

```
simpcommerce-api/
├── app/
│   └── Modules/
│       ├── Core/
│       │   ├── Enums/          # 11 domain enums
│       │   ├── Traits/         # ApiResponse, QueryFilter, StoreScope,
│       │   │                   #   AuthorizesOwnership, HandlesPasswordUpdate
│       │   └── Repositories/   # Base Repository class
│       ├── Identity/           # AuthController, UserController, ProfileController,
│       │                       #   AdminMiddleware, User model (UserRole enum)
│       ├── Store/              # StoreController, ResolveStore middleware, Store model
│       ├── Catalog/            # ProductController, ProductVariantController,
│       │                       #   CategoryController, StorefrontController,
│       │                       #   ProductService, ProductImportService,
│       │                       #   ProductExportService, StorefrontService, MediaService
│       ├── Customer/           # CustomerController, CustomerAuthController,
│       │                       #   CustomerProfileController, AddressController,
│       │                       #   OAuthController, Customer model (Authenticatable),
│       │                       #   Address model (AddressType enum)
│       ├── Sales/              # OrderController, InvoiceController,
│       │                       #   OrderService, InvoiceService,
│       │                       #   InvoiceNumberGenerator, Order/OrderItem/Payment/Invoice models
│       ├── Inventory/          # StockMovementController, StockMovement model
│       ├── Promotion/          # DiscountController, Discount model
│       ├── Supplier/           # SupplierController, Supplier model
│       ├── Cash/               # CashSessionController, CashSession model
│       ├── Audit/              # AuditLogController, AuditLog model
│       ├── Report/             # DashboardController, ReportController,
│       │                       #   DashboardService, ReportService
│       ├── System/             # BackupController (driver-aware: pg_dump/mysqldump/copy)
│       └── ECommerce/          # CartController, CheckoutController,
│                               #   MyOrderController, WishlistController,
│                               #   OnlineOrderService, CartService,
│                               #   WishlistService, MyOrderService,
│                               #   CartItemRepository, WishlistItemRepository,
│                               #   ShipmentRepository,
│                               #   CartItem, WishlistItem, Shipment models
├── database/
│   ├── factories/              # 15 model factories
│   ├── migrations/             # 31 migration files
│   └── seeders/                # DatabaseSeeder (store + staff + data)
├── routes/
│   ├── api.php                 # Master route loader (4 middleware groups)
│   └── modules/                # 15 per-module route files
├── docs/                       # Project documentation (README, API, SPEC, ARCH)
└── tests/
    ├── Feature/Api/            # 19 feature test files (147 tests)
    └── ApiTestCase.php         # Base test case with helpers
```

---

## 8. Factories (15 total)

| Factory                  | Model             |
|--------------------------|-------------------|
| `UserFactory`            | User              |
| `StoreFactory`           | Store             |
| `CategoryFactory`        | Category          |
| `ProductFactory`         | Product           |
| `ProductVariantFactory`  | ProductVariant    |
| `CustomerFactory`        | Customer          |
| `OrderFactory`           | Order             |
| `OrderItemFactory`       | OrderItem         |
| `PaymentFactory`         | Payment           |
| `InvoiceFactory`         | Invoice           |
| `SupplierFactory`        | Supplier          |
| `DiscountFactory`        | Discount          |
| `CashSessionFactory`     | CashSession       |
| `CartItemFactory`        | CartItem          |
| `WishlistItemFactory`    | WishlistItem      |

---

## 9. Current Status

### ✅ Completed

- [x] **14 modules** fully operational
- [x] **Multi-store**: `store_id` on 8 tables, `ResolveStore` middleware, `StoreScope` trait
- [x] **Storefront API**: `/api/storefront/products|categories|settings` public endpoints
- [x] **ECommerce**: Cart, Wishlist, COD checkout, shipments, customer orders
- [x] **OAuth**: Google social login via Socialite → Session Cookie (`OAuthController`)
- [x] **Enums**: 11 strongly-typed enums replacing all magic strings
- [x] **Repository pattern**: Base `Repository` class + ECommerce repositories
- [x] **Service layer**: 14 dedicated services extracted from controllers
- [x] **Shared traits**: `StoreScope`, `AuthorizesOwnership`, `HandlesPasswordUpdate`
- [x] **103 routes**: Fully registered and mapped
- [x] **147 tests**: All passing
- [x] **Backup**: Driver-aware (`pg_dump`/`mysqldump`/copy)

### ⏳ Next Steps

1. **Discount/Supplier storefront scoping** — Apply `store_id` filter in storefront for discounts and suppliers
2. **Payment gateways** — KBZ Pay / Wave Money (deferred)
3. **Plain products** — Support products without variants (deferred)
4. **Storefront cookie auth** — Switch to Sanctum HttpOnly cookie auth for storefronts
5. **Admin online order management** — Ship/deliver actions in dashboard
