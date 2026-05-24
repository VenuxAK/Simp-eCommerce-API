# SimpCommerce API

Laravel 13 REST API backend for SimpCommerce — a modular commerce platform with POS, multi-storefront e-commerce, customer CRM, inventory management, and bilingual (EN/MY) support.

## Requirements

- PHP 8.3+
- PostgreSQL 16+ (or SQLite for testing/lightweight deployments)
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
# 136 tests covering all endpoints
```

## API Endpoints

All endpoints are prefixed with `/api`. Routes are organized into 14 per-module files under `routes/modules/` and loaded by a master route loader (`routes/api.php`).

### Staff Auth (Public)
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login` | Staff login (rate-limited: 10/min) |
| POST | `/api/auth/logout` | Revoke token |
| GET | `/api/auth/me` | Current user |

### Customer Auth (Public)
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/customer/register` | Register (rate-limited) |
| POST | `/api/customer/login` | Login (rate-limited) |

### Products & Variants
| Method | Endpoint | Description |
|---|---|---|
| GET/POST/PUT/DELETE | `/api/products` | CRUD (paginated, write=admin) |
| POST | `/api/products/{id}/image` | Upload image |
| GET | `/api/products/export/csv` | Export CSV |
| POST | `/api/products/import/csv` | Import CSV (admin) |
| GET | `/api/products/{id}/labels` | Barcode labels |
| PATCH | `/api/variants/{id}/stock` | Adjust stock |
| POST | `/api/variants/{id}/image` | Variant image |
| GET | `/api/variants/by-sku/{sku}` | Barcode lookup |

### Orders (POS)
| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/api/orders` | List (paginated) / Create |
| PATCH | `/api/orders/{id}/status` | Update status (admin) |
| POST | `/api/orders/{id}/return` | Item-level return (admin) |

### Invoices
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/invoices` | List (paginated) |
| GET | `/api/invoices/{id}` | Detail |
| GET | `/api/invoices/{id}/pdf` | Download PDF |
| GET | `/api/invoices/{id}/receipt` | Thermal receipt |

### Customer Portal (Authenticated Customer)
| Method | Endpoint | Description |
|---|---|---|
| GET/POST/PUT/DEL | `/api/addresses` | Address book CRUD |
| GET/POST/PUT/DEL | `/api/cart` | Shopping cart |
| POST | `/api/checkout` | Place COD order |
| GET | `/api/my/orders` | Order history |
| POST | `/api/my/orders/{id}/cancel` | Cancel order |

### Other
- Categories, Customers, Suppliers, Discounts: CRUD (paginated, write=admin)
- Cash Sessions: open/close/list/active
- Stock Movements: list with filters (admin only)
- Backup: create/list/download database snapshots (admin only)
- Stores: full CRUD (admin only)
- Reports: sales, best-sellers, payment methods
- Dashboard: summary with low stock alerts
- Users: admin-only CRUD (paginated)
- Audit Log: admin-only (paginated)
- Profile: self-service update

Full documentation: see `SPECIFICATION.md`

## Key Features

- **Modular monolith**: 14 modules under `app/Modules/` with per-module routes
- **Token authentication** via Laravel Sanctum (two guards: staff + customer)
- **Token lifetimes**: 24h staff, 7d customers
- **Role-based access** (Admin/Staff) with admin middleware
- **Customer auth**: Register/login/logout with Sanctum customer guard
- **E-Commerce**: Server-side cart, COD checkout, shipments, online order management
- **Multi-store**: store_id nullable FK on 6 tables, opt-in scoping via middleware
- **Validation errors** translated to user's locale
- **Atomic stock operations** with transaction safety
- **Idempotent status transitions** (no double-cancel)
- **Thread-safe invoice numbering** (INV-{YYYYMMDD}-{XXXX})
- **Password policy**: min 8 chars, uppercase + lowercase + digit
- **136 feature tests** running on SQLite in-memory
