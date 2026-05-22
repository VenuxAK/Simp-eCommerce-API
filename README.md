# SimpCommerce API

Laravel 13 REST API backend for SimpCommerce — a home-use Point of Sale system for clothing stores.

## Requirements

- PHP 8.3+
- SQLite (included, zero setup)
- Composer

## Quick Start

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

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
| Admin | `admin@simppos.test` | `password` |
| Staff | `staff@simppos.test` | `password` |

## Testing

```bash
php artisan test
# 86 tests covering all endpoints
```

## API Endpoints

All endpoints are prefixed with `/api` and protected by `auth:sanctum` (except login).

### Auth
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login` | Login (rate-limited) |
| POST | `/api/auth/logout` | Revoke token |
| GET | `/api/auth/me` | Current user |

### Products & Variants
| Method | Endpoint | Description |
|---|---|---|
| GET/POST/PUT/DELETE | `/api/products` | CRUD (paginated) |
| POST | `/api/products/{id}/image` | Upload image |
| GET | `/api/products/export/csv` | Export CSV |
| POST | `/api/products/import/csv` | Import CSV |
| GET | `/api/products/{id}/labels` | Barcode labels |
| PATCH | `/api/variants/{id}/stock` | Adjust stock |
| POST | `/api/variants/{id}/image` | Variant image |
| GET | `/api/variants/by-sku/{sku}` | Barcode lookup |

### Orders
| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/api/orders` | List (paginated) / Create |
| PATCH | `/api/orders/{id}/status` | Update status |
| POST | `/api/orders/{id}/return` | Item-level return |

### Invoices
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/invoices` | List (paginated) |
| GET | `/api/invoices/{id}` | Detail |
| GET | `/api/invoices/{id}/pdf` | Download PDF |
| GET | `/api/invoices/{id}/receipt` | Thermal receipt |

### Other
- Categories, Customers, Suppliers, Discounts: full CRUD (paginated)
- Cash Sessions: open/close/list/active
- Stock Movements: list with filters (paginated)
- Backup: create/list/download database snapshots
- Reports: sales, best-sellers, payment methods
- Dashboard: summary with low stock alerts
- Users: admin-only CRUD (paginated)
- Audit Log: admin-only (paginated)
- Profile: self-service update

Full documentation: see `SPECIFICATION.md`

## Key Features

- **Token authentication** via Laravel Sanctum
- **Role-based access** (Admin/Staff) with admin middleware
- **Validation errors** translated to user's locale
- **Atomic stock operations** with transaction safety
- **Idempotent status transitions** (no double-cancel)
- **Thread-safe invoice numbering**
- **86 feature tests** running on SQLite in-memory
