# SimpCommerce API

> Modular commerce platform — self-hosted, open-source, built for Myanmar's mid-market retailers.

**Stack**: Laravel 13 · PHP 8.4 · PostgreSQL 16+ · 14 modules · 112 routes · 147+ tests

**Repositories**: `simpcommerce-api` (this repo) · `simpcommerce-dashboard` (Vue 3 SPA) · `simpcommerce-storefront-*` (Nuxt 4 SSR)

---

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure PostgreSQL in .env (defaults: 127.0.0.1:5432, simp_commerce)
php artisan migrate --seed
php artisan storage:link
php artisan serve   # → http://localhost:8000
```

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Root | `admin@simppos.test` | `Pass1234` |
| Staff | `staff@simppos.test` | `Pass1234` |

## Testing

```bash
php artisan test --compact
```

## Documentation

| Document | Covers |
|----------|--------|
| [`SPECIFICATION.md`](docs/SPECIFICATION.md) | Tech stack, database schema, enums, security, testing strategy |
| [`ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Module design, multi-store model, service layer, route architecture |
| [`API.md`](docs/API.md) | Complete endpoint reference (112 routes, request/response formats) |
| [`PRD.md`](docs/PRD.md) | Product requirements, user personas, roadmap |
| [`PROJECT_ANALYSIS.md`](docs/PROJECT_ANALYSIS.md) | Codebase analysis, business domains, module inventory |

## Architecture

**Modular monolith** — 14 domain modules under `app/Modules/` sharing a common `Core` kernel.

```
Core → Identity → Store → Catalog → Customer → Sales → ECommerce
     → Inventory → Promotion → Supplier → Cash → Audit → Report → System
```

**Multi-store**: `store_id` FK on 9 tables, `ResolveStore` middleware reads `X-Store` header.

**Auth**: Sanctum token-based, two guards (`api` for staff 24h, `customer` for customers 7d) + Google OAuth.

**Routes**: 4 middleware groups → 15 per-module route files → 112 endpoints.

See [`ARCHITECTURE.md`](docs/ARCHITECTURE.md) for module diagrams and design rationale.

## API Overview

| Group | Prefix | Auth | Purpose |
|-------|--------|------|---------|
| Public | `/auth/*`, `/customer/*` | None | Login, register, OAuth |
| Storefront | `/storefront/*` | None (public) | Product catalog, categories, brands |
| Customer Portal | `/cart`, `/checkout`, `/addresses`, `/wishlist`, `/my/*` | `auth:customer` | Shopping, orders, profile |
| Staff Dashboard | `/products`, `/orders`, `/invoices`, `/customers`, etc. | `auth:sanctum` | Admin, POS, CRM, reports |

Full reference: [`API.md`](docs/API.md).

## Key Capabilities

- **Multi-store e-commerce** — public Nuxt storefronts per store, scoped by `X-Store` header
- **Customer portal** — server-side cart, COD checkout, wishlist, order history, cancellations
- **Staff dashboard** — product/inventory management, POS orders, CRM, invoicing (PDF/receipt)
- **Myanmar localization** — EN/MY dual-language, MMK currency, Myanmar numerals
- **RBAC** — 3 staff roles (root, store_admin, staff) with middleware-enforced permissions
- **Atomic stock** — row-locked checkout, idempotent cancellation
- **Backup system** — driver-aware (pg_dump/mysqldump/copy), non-blocking via queue jobs

## Requirements

- PHP 8.4+
- PostgreSQL 16+
- Composer
- Node.js (for frontend assets — `simpcommerce-dashboard` and `simpcommerce-storefront-*` repos)
