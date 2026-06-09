# SimpCommerce API

Laravel 13 REST API backend for SimpCommerce — a modular commerce platform with POS, multi-storefront e-commerce, customer CRM, inventory management, and bilingual (EN/MY) support.

**Repositories**: `simpcommerce-api` (this repo), `simpcommerce-dashboard` (Vue 3 SPA), `simpcommerce-storefront-*` (Nuxt 4 SSR)

## Requirements

- PHP 8.4+
- PostgreSQL 16+ (SQLite for testing)
- Composer
- Node.js (for asset bundling)

## Quick Start

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Ensure PostgreSQL is running with a database created.
# Default config expects: host=127.0.0.1, port=5432,
# database=simp_commerce, user=postgres, password=secret
# (adjust .env if your setup differs)

# Run database migrations and seed
php artisan migrate --seed

# Create storage symlink for images
php artisan storage:link

# Start the development server
php artisan serve
# → http://localhost:8000
```

## Default Credentials

| Role       | Email                  | Password   |
|------------|------------------------|------------|
| Root       | `admin@simppos.test`   | `Pass1234` |
| Staff      | `staff@simppos.test`   | `Pass1234` |

## Testing

```bash
php artisan test --compact
# 147 tests covering all endpoints
```

## Architecture

**Modular monolith** — 14 modules under `app/Modules/` with per-module route files under `routes/modules/`. Master loader at `routes/api.php`.

```
Core → Identity → Store → Catalog → Customer → Sales → Inventory
     → Promotion → Supplier → Cash → Audit → Report → System → ECommerce
```

**Database**: PostgreSQL (development/production), SQLite in-memory (tests).

**Multi-store**: `store_id` FK on 6 tables. `ResolveStore` middleware reads `X-Store` header, scopes catalog queries by store.

**Auth**: Sanctum token-based, two guards (`web` + `customer`), 24h staff / 7d customer tokens.

## API Endpoints

All endpoints are prefixed with `/api`. Routes are organized into 15 per-module files. **103 total routes**.

### Storefront (Public — no auth)

| Method | Endpoint                           | Description                              |
|--------|------------------------------------|------------------------------------------|
| GET    | `/api/storefront/products`         | Paginated products (store-scoped)        |
| GET    | `/api/storefront/products/{slug}`  | Product detail with variants             |
| GET    | `/api/storefront/categories`       | Category list with product counts        |
| GET    | `/api/storefront/settings`         | Store config (branding, contact, currency) |

### Staff Auth & Dashboard (`auth:sanctum`)

| Module             | Description                                                                |
|--------------------|----------------------------------------------------------------------------|
| Auth               | Staff login/logout/me                                                      |
| Profile            | Self-update name/email/password                                             |
| Users              | CRUD (admin only)                                                           |
| Products           | CRUD, CSV import/export, image upload, barcode labels                       |
| Variants           | Stock adjustment, image upload, barcode lookup by SKU                       |
| Categories         | CRUD (write = admin)                                                        |
| Orders             | List/detail/create (POS), status updates (admin), item returns (admin)      |
| Invoices           | List/detail/print/PDF/receipt                                               |
| Customers (CRM)    | CRUD + order history                                                        |
| Suppliers          | CRUD (admin)                                                                |
| Discounts          | CRUD + active list (admin)                                                  |
| Stock Movements    | Filtered list (admin)                                                       |
| Cash Sessions      | Open/close/active session                                                   |
| Stores             | CRUD (admin)                                                                |
| Backups            | Driver-aware create/list/download (pg_dump/mysqldump/copy)                  |
| Reports            | Dashboard summary, sales, best-sellers, payment methods                     |
| Audit Logs         | Filtered list (admin)                                                       |

### Customer Portal (`auth:customer`)

| Method         | Endpoint                          | Description                    |
|----------------|-----------------------------------|--------------------------------|
| POST           | `/api/customer/register\|login\|logout` | Customer auth               |
| GET/PUT        | `/api/customer/me\|profile`       | Profile management             |
| CRUD           | `/api/addresses`                  | Address book                   |
| PUT            | `/api/addresses/{id}/default`     | Set default address            |
| CRUD           | `/api/cart`                       | Shopping cart                  |
| DELETE         | `/api/cart`                       | Clear entire cart              |
| POST           | `/api/checkout`                   | Place COD order                |
| GET            | `/api/checkout/validate`          | Validate stock before checkout |
| GET            | `/api/my/orders`                  | Order history                  |
| POST           | `/api/my/orders/{id}/cancel`      | Cancel order (processing only) |
| GET/POST/DELETE| `/api/wishlist`                   | Wishlist management            |

Full documentation: see [`SPECIFICATION.md`](SPECIFICATION.md), [`ARCHITECTURE.md`](ARCHITECTURE.md), and [`API.md`](API.md).

## Key Features

- **Modular monolith**: 14 modules, 15 route files, 103 routes, 147 tests
- **Sanctum auth**: Token-based, two guards (staff 24h, customer 7d)
- **OAuth (Google)**: Customer social login via Socialite → Session Cookie
- **E-Commerce**: Server-side cart, COD checkout, shipments, wishlist, online order management
- **Multi-store**: `store_id` scoping via `ResolveStore` middleware, public storefront API
- **Driver-aware backup**: `pg_dump`/`mysqldump`/file copy based on database driver
- **i18n**: English + Burmese server-side error translations
- **Atomic stock**: Transaction-safe decrement, idempotent cancel guard
- **PDF/Receipt**: Invoice PDF download + thermal receipt format
- **Password policy**: Min 8 chars, uppercase + lowercase + digit
- **Enums**: Strongly-typed enums for all domain values (roles, statuses, types)
- **Repository pattern**: Core `Repository` base class; ECommerce module uses CartItem, Wishlist, Shipment repositories
- **Service layer**: Dedicated services for Orders, Invoices, Cart, Checkout, Wishlist, Reports, Dashboard, Products, Storefront

## Directory Structure

```
api/
├── app/
│   └── Modules/           # 14 domain modules
│       ├── Core/          # Enums, Traits (ApiResponse, QueryFilter, StoreScope,
│       │                  #   AuthorizesOwnership, HandlesPasswordUpdate), Repository base
│       ├── Identity/      # Auth, Users, Profiles, UserRole enum, AdminMiddleware
│       ├── Store/         # StoreController, ResolveStore middleware, Store model
│       ├── Catalog/       # Products, Variants, Categories, Storefront, ProductService,
│       │                  #   ProductImportService, ProductExportService, StorefrontService
│       ├── Customer/      # CRM, CustomerAuth, OAuthController, AddressBook
│       ├── Sales/         # Orders, Invoices, OrderService, InvoiceService,
│       │                  #   InvoiceNumberGenerator
│       ├── Inventory/     # StockMovements, StockService
│       ├── Promotion/     # Discounts, DiscountService
│       ├── Supplier/      # Suppliers
│       ├── Cash/          # CashSessions, CashSessionService
│       ├── Audit/         # AuditLogs
│       ├── Report/        # Dashboard & Reports (DashboardService, ReportService)
│       ├── System/        # Backups (driver-aware)
│       └── ECommerce/     # Cart, Checkout, MyOrders, Wishlist, OnlineOrderService,
│                          #   CartItemRepository, WishlistItemRepository, ShipmentRepository
├── database/
│   ├── factories/         # 15 model factories
│   ├── migrations/        # 31 migration files
│   └── seeders/
├── routes/
│   ├── api.php            # Master route loader
│   └── modules/           # 15 per-module route files
├── docs/                  # Project documentation
└── tests/                 # 147 tests (Feature + Unit)
```
