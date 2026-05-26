# SimpCommerce API

Laravel 13 REST API backend for SimpCommerce — a modular commerce platform with POS, multi-storefront e-commerce, customer CRM, inventory management, and bilingual (EN/MY) support.

**Repositories**: `simpcommerce-api` (this repo), `simpcommerce-dashboard` (Vue 3 SPA), `simpcommerce-storefront-*` (Nuxt 4 SSR)

## Requirements

- PHP 8.3+
- PostgreSQL 16+ (SQLite for testing/lightweight deployments)
- Composer

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

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@simppos.test` | `Pass1234` |
| Staff | `staff@simppos.test` | `Pass1234` |

## Testing

```bash
php artisan test
# 147 tests covering all endpoints
```

## Architecture

**Modular monolith** — 14 modules under `app/Modules/` with per-module route files under `routes/modules/`. Master loader at `routes/api.php` (~22 lines).

```
Core → Identity → Store → Catalog → Customer → Sales → Inventory
→ Promotion → Supplier → Cash → Audit → Report → System → ECommerce
```

**Database**: PostgreSQL (development/production), SQLite in-memory (tests).

**Multi-store**: `store_id` as nullable FK on 6 tables. ResolveStore middleware reads `X-Store` header, scopes catalog queries by store.

**Auth**: Sanctum token-based, two guards (`web` + `customer`), 24h staff / 7d customer tokens.

## API Endpoints

All endpoints are prefixed with `/api`. Routes are organized into 15 per-module files.

### Storefront (Public — no auth)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/storefront/products` | Paginated products (store-scoped, filterable) |
| GET | `/api/storefront/products/{slug}` | Product detail with variants |
| GET | `/api/storefront/categories` | Category list with product counts |
| GET | `/api/storefront/settings` | Store config (branding, contact, currency) |

### Staff Auth & Dashboard (auth:sanctum)

| Module | Description |
|---|---|
| Auth | staff login/logout/me |
| Products | CRUD, CSV import/export, images, barcode labels |
| Categories | CRUD (write=admin) |
| Orders | list/detail/create, status updates, item returns |
| Invoices | list/detail/print/pdf/receipt |
| Customers | CRM with order history |
| Suppliers, Discounts, Stock, Cash, Stores, Users, Audit Logs | Full CRUD |
| Reports | dashboard summary, sales, best-sellers, payment methods |
| Backups | driver-aware create/download (pg_dump/mysqldump/copy) |

### Customer Portal (auth:customer)

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/customer/register\|login\|logout` | Auth |
| GET/PUT | `/api/customer/me\|profile` | Profile |
| CRUD | `/api/addresses` | Address book |
| CRUD | `/api/cart` | Shopping cart |
| POST | `/api/checkout` | COD order placement |
| GET | `/api/my/orders` | Order history |
| POST | `/api/my/orders/{id}/cancel` | Cancel order |

Full documentation: see `SPECIFICATION.md` and `ARCHITECTURE.md`

## Key Features

- **Modular monolith**: 14 modules, per-module routes, 147 tests
- **Sanctum auth**: Token-based, two guards (staff 24h, customer 7d)
- **E-Commerce**: Server-side cart, COD checkout, shipments, online order management
- **Multi-store**: store_id scoping via ResolveStore middleware, public storefront API
- **Driver-aware backup**: pg_dump/mysqldump/file copy based on database driver
- **i18n**: English + Burmese, server errors translated via custom mapping
- **Atomic stock**: Transaction-safe decrement, idempotent transitions
- **PDF/Receipt**: Invoice PDF download + thermal receipt format
- **Password policy**: Min 8 chars, uppercase + lowercase + digit
