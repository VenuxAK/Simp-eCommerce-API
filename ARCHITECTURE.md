# SimpCommerce — Modular Monolith Architecture

> **Status**: Draft Plan
> **Target Branch**: `arch/modular-monolith`
> **Migration**: Incremental (module by module)

---

## 1. Motivation

The current codebase (`SimpPOS`) was built as a straightforward monolithic Laravel app with a flat directory structure. While this worked for a single POS + one storefront, the system now needs to support:

- **Multiple storefronts** — clothing, electronics, home appliances, each with their own public website
- **Multiple sales channels** — POS (in-store), online storefronts, future channels (WhatsApp, Facebook Shop)
- **Clearer domain boundaries** — developers need to understand and modify specific business areas without touching unrelated code

A **Modular Monolith** gives us clean separation within a single deployable unit — no microservices complexity, no network overhead, but the same disciplined boundaries you'd find in a distributed system.

---

## 2. Vision: Unified Commerce Platform

```
                         ┌─────────────────────────────────────┐
                         │         SimpCommerce API            │
                         │         (Modular Monolith)          │
                         │                                     │
                         │  ┌──────┐ ┌──────┐ ┌──────┐       │
┌──────────────┐         │  │Catalog│ │Sales │ │ Iden-│  ...  │
│  Storefront  │─────────┼─▶│Module │ │Module│ │tity  │       │
│  (Clothing)  │         │  └──────┘ └──────┘ └Module│       │
└──────────────┘         │  ┌──────┐ ┌──────┐ └──────┘       │
                         │  │Store  │ │Inven-│                 │
┌──────────────┐         │  │Module │ │tory  │                 │
│  Storefront  │─────────┼─▶│       │ │Module│                 │
│ (Electronics)│         │  └──────┘ └──────┘                 │
└──────────────┘         │         ┌──────────┐               │
                         │         │  Core/   │               │
┌──────────────┐         │         │ Shared   │               │
│  Storefront  │─────────┼─▶       │ Kernel   │               │
│(Home Appl.)  │         │         └──────────┘               │
└──────────────┘         └──────────┬──────────────────────────┘
                                    │
                         ┌──────────▼──────────┐
                         │    PostgreSQL        │
                         │   (single database)  │
                         └─────────────────────┘

                      ┌──────────────────────┐
                      │   Vue 3 Dashboard    │
                      │   (staff/admin UI)   │
                      └──────────────────────┘
```

### Key Principles

| Principle | Description |
|-----------|-------------|
| **Modules are optional** | You can deploy with only the modules you need |
| **Shared Kernel** | Core/ module provides base classes, traits, enums that all modules depend on |
| **Module autonomy** | Each module owns its models, migrations, routes, controllers, tests |
| **Cross-module communication** | Via interfaces/contracts, never direct model access across module boundaries |
| **Store-scoped** | All data is scoped to a store (multi-tenant within a single database) |
| **API-first** | All module functionality exposed through REST API endpoints |

---

## 3. Project Rename

The current name **SimpPOS** reflects only the Point-of-Sale use case. Since the system is evolving into a multi-storefront commerce platform, I suggest renaming to something broader.

### Candidates

| Name | Rationale |
|------|-----------|
| **SimpCommerce** | "Simple Commerce" — covers POS, e-commerce, multi-store. Keeps the "Simp" brand. Most descriptive. |
| **SimpMerch** | "Simple Merchandise" — shorter, but less obvious |
| **Merx** | Latin for "goods/commerce" — short, memorable, brandable |
| **SimpCore** | Emphasizes it's the core engine for multiple frontends |

> **My recommendation**: **SimpCommerce** — it keeps the existing brand recognition while accurately describing what the system has grown into.

### What Changes

| Artifact | Current | New |
|----------|---------|-----|
| Root directory | `SimpPOS` | `simpcommerce` |
| API directory | `SimpPOS/api` | `simpcommerce/api` |
| Frontend dir | `SimpPOS/frontend` | `simpcommerce/dashboard` |
| Storefront dir | — | `simpcommerce/storefront-{name}` |
| Docker images | simppos-* | simpcommerce-* |
| App name | SimpPOS | SimpCommerce |
| DB name | simppos | simpcommerce |

> **Decision needed**: Confirm if/when to rename. Can happen at any point — no rush.

---

## 4. Module Map

### Module Inventory

```
┌─────────────────────────────────────────────────────────────────┐
│                        SimpCommerce API                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐       │
│  │  Core    │  │ Identity │  │  Store   │  │ Catalog  │       │
│  │(Shared   │  │ (Auth,   │  │ (Multi-  │  │ (Products│       │
│  │ Kernel)  │  │  Users)  │  │  store)  │  │ & Categ) │       │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘       │
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐       │
│  │ Customer │  │  Sales   │  │Inventory │  │Promotion │       │
│  │ (CRM,    │  │ (Orders, │  │ (Stock,  │  │(Discounts│       │
│  │  Cart)   │  │  POS)    │  │ Movement)│  │ & Rules) │       │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘       │
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐       │
│  │ Supplier │  │   Cash   │  │  Audit   │  │  Report  │       │
│  │ (Vendors)│  │  (Sessions│  │  (Logs)  │  │ (Analytics       │
│  │          │  │  & Reg.) │  │          │  │  & Dashboard)    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘       │
│                                                                 │
│  ┌──────────┐                                                   │
│  │  System  │                                                   │
│  │ (Backup, │                                                   │
│  │  Config) │                                                   │
│  └──────────┘                                                   │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                  E-Commerce Module                       │   │
│  │  (Cart, Checkout, Payment Gateways, ⋯, Storefront API)   │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Module Dependency Graph

```
                    ┌──────────┐
                    │   Core   │ (no dependencies)
                    └────┬─────┘
                         │
              ┌──────────┼──────────┐
              ▼          ▼          ▼
         ┌────────┐ ┌────────┐ ┌────────┐
         │Identity│ │ Store  │ │  Audit │
         └────┬───┘ └────┬───┘ └────────┘
              │          │
              ▼          ▼
         ┌──────────────────────────────────────┐
         │  Catalog   Customer   Supplier        │
         │  (depends on Core + Store + Identity) │
         └──────────────────────────────────────┘
              │          │
              ▼          ▼
         ┌──────────────────────────────────────┐
         │     Sales    Inventory    Promotion   │
         │  (depends on Catalog + Customer)      │
         └──────────────────────────────────────┘
              │          │
              ▼          ▼
         ┌──────────────────────────────────────┐
         │         E-Commerce Module            │
         │  (depends on Sales + Customer +      │
         │   Catalog + Payment Gateways)        │
         └──────────────────────────────────────┘

    Report     — depends on Sales, Inventory, Cash
    Cash       — depends on Identity, Sales
    System     — depends on Core only
```

---

## 5. Directory Structure

### Target Module Layout

```
api/
├── app/
│   ├── Modules/
│   │   ├── Core/                          # Shared Kernel
│   │   │   ├── Traits/
│   │   │   │   ├── ApiResponse.php        # (from Traits/)
│   │   │   │   └── QueryFilter.php        # (from Traits/)
│   │   │   ├── Enums/
│   │   │   │   ├── InvoiceStatus.php
│   │   │   │   ├── OrderStatus.php
│   │   │   │   └── PaymentMethod.php
│   │   │   └── Helpers/
│   │   │       └── helpers.php
│   │   │
│   │   ├── Identity/                      # Auth, Users, Roles
│   │   │   ├── Config/
│   │   │   │   └── permissions.php
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       └── 0001_01_01_000000_create_users_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── UserController.php
│   │   │   │   │   └── ProfileController.php
│   │   │   │   ├── Middleware/
│   │   │   │   │   └── AdminMiddleware.php
│   │   │   │   ├── Requests/
│   │   │   │   │   ├── LoginRequest.php
│   │   │   │   │   ├── StoreUserRequest.php
│   │   │   │   │   └── UpdateUserRequest.php
│   │   │   │   └── Resources/
│   │   │   │       └── UserResource.php
│   │   │   ├── Models/
│   │   │   │   └── User.php
│   │   │   ├── Providers/
│   │   │   │   └── IdentityServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       ├── AuthTest.php
│   │   │       ├── UserTest.php
│   │   │       └── ProfileTest.php
│   │   │
│   │   ├── Store/                         # Multi-Store Management
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       └── xxxx_create_stores_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   └── StoreController.php
│   │   │   │   ├── Middleware/
│   │   │   │   │   └── ResolveStore.php
│   │   │   │   └── Resources/
│   │   │   │       └── StoreResource.php
│   │   │   ├── Models/
│   │   │   │   └── Store.php
│   │   │   ├── Providers/
│   │   │   │   └── StoreServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       └── StoreTest.php
│   │   │
│   │   ├── Catalog/                       # Products, Categories, Variants
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       ├── xxxx_create_categories_table.php
│   │   │   │       ├── xxxx_create_products_table.php
│   │   │   │       └── xxxx_create_product_variants_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   ├── ProductController.php
│   │   │   │   │   ├── ProductVariantController.php
│   │   │   │   │   ├── CategoryController.php
│   │   │   │   │   ├── PublicProductController.php  # (storefront-facing)
│   │   │   │   │   └── PublicCategoryController.php
│   │   │   │   ├── Requests/
│   │   │   │   ├── Resources/
│   │   │   │   └── Middleware/
│   │   │   │       └── CatalogScopedByStore.php
│   │   │   ├── Models/
│   │   │   │   ├── Product.php
│   │   │   │   ├── Category.php
│   │   │   │   └── ProductVariant.php
│   │   │   ├── Services/
│   │   │   │   ├── ProductImportService.php
│   │   │   │   ├── ProductExportService.php
│   │   │   │   └── MediaService.php
│   │   │   ├── Providers/
│   │   │   │   └── CatalogServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       ├── ProductTest.php
│   │   │       ├── CategoryTest.php
│   │   │       └── VariantTest.php
│   │   │
│   │   ├── Customer/                      # Customers, Addresses, Cart
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       ├── xxxx_create_customers_table.php
│   │   │   │       └── xxxx_create_addresses_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   ├── CustomerController.php
│   │   │   │   │   ├── CustomerAuthController.php
│   │   │   │   │   ├── AddressController.php
│   │   │   │   │   └── CartController.php
│   │   │   │   ├── Requests/
│   │   │   │   └── Resources/
│   │   │   ├── Models/
│   │   │   │   ├── Customer.php
│   │   │   │   └── Address.php
│   │   │   ├── Providers/
│   │   │   │   └── CustomerServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       ├── CustomerTest.php
│   │   │       ├── CartTest.php
│   │   │       └── AddressTest.php
│   │   │
│   │   ├── Sales/                         # Orders, Invoices, Payments
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       ├── xxxx_create_orders_table.php
│   │   │   │       ├── xxxx_create_order_items_table.php
│   │   │   │       ├── xxxx_create_payments_table.php
│   │   │   │       └── xxxx_create_invoices_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   ├── OrderController.php
│   │   │   │   │   ├── InvoiceController.php
│   │   │   │   │   └── MyOrderController.php
│   │   │   │   ├── Requests/
│   │   │   │   └── Resources/
│   │   │   ├── Models/
│   │   │   │   ├── Order.php
│   │   │   │   ├── OrderItem.php
│   │   │   │   ├── Payment.php
│   │   │   │   └── Invoice.php
│   │   │   ├── Services/
│   │   │   │   ├── OrderService.php
│   │   │   │   └── InvoiceNumberGenerator.php
│   │   │   ├── Providers/
│   │   │   │   └── SalesServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       ├── OrderTest.php
│   │   │       ├── InvoiceTest.php
│   │   │       └── ReturnOrderTest.php
│   │   │
│   │   ├── Inventory/                     # Stock, Warehouses
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       └── xxxx_create_stock_movements_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   └── StockMovementController.php
│   │   │   │   └── Resources/
│   │   │   ├── Models/
│   │   │   │   └── StockMovement.php
│   │   │   ├── Providers/
│   │   │   │   └── InventoryServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       └── StockMovementTest.php
│   │   │
│   │   ├── Promotion/                     # Discounts, Coupons
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       └── xxxx_create_discounts_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   └── DiscountController.php
│   │   │   │   └── Resources/
│   │   │   ├── Models/
│   │   │   │   └── Discount.php
│   │   │   ├── Services/
│   │   │   │   └── DiscountService.php
│   │   │   ├── Providers/
│   │   │   │   └── PromotionServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       └── DiscountTest.php
│   │   │
│   │   ├── Supplier/                      # Vendors & Suppliers
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       └── xxxx_create_suppliers_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   └── SupplierController.php
│   │   │   │   └── Resources/
│   │   │   ├── Models/
│   │   │   │   └── Supplier.php
│   │   │   ├── Providers/
│   │   │   │   └── SupplierServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       └── SupplierTest.php
│   │   │
│   │   ├── Cash/                          # Cash Drawer Sessions
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       └── xxxx_create_cash_sessions_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   └── CashSessionController.php
│   │   │   │   └── Resources/
│   │   │   ├── Models/
│   │   │   │   └── CashSession.php
│   │   │   ├── Providers/
│   │   │   │   └── CashServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       └── CashSessionTest.php
│   │   │
│   │   ├── Audit/                         # Activity Logging
│   │   │   ├── Database/
│   │   │   │   └── Migrations/
│   │   │   │       └── xxxx_create_audit_logs_table.php
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   └── AuditLogController.php
│   │   │   │   └── Resources/
│   │   │   ├── Models/
│   │   │   │   └── AuditLog.php
│   │   │   ├── Providers/
│   │   │   │   └── AuditServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       └── AuditLogTest.php
│   │   │
│   │   ├── Report/                        # Analytics & Dashboards
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   ├── DashboardController.php
│   │   │   │   │   └── ReportController.php
│   │   │   │   └── Resources/
│   │   │   ├── Services/
│   │   │   │   └── ReportService.php
│   │   │   ├── Providers/
│   │   │   │   └── ReportServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       ├── DashboardTest.php
│   │   │       └── ReportTest.php
│   │   │
│   │   ├── System/                        # Backups, Config
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   └── BackupController.php
│   │   │   │   └── Requests/
│   │   │   ├── Providers/
│   │   │   │   └── SystemServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │       └── BackupTest.php
│   │   │
│   │   └── ECommerce/                     # Online Storefront Features
│   │       ├── Database/
│   │       │   └── Migrations/
│   │       │       ├── xxxx_create_cart_items_table.php
│   │       │       ├── xxxx_create_shipments_table.php
│   │       │       └── xxxx_create_payment_transactions_table.php
│   │       ├── Http/
│   │       │   ├── Controllers/
│   │       │   │   ├── CheckoutController.php
│   │       │   │   └── PaymentWebhookController.php
│   │       │   ├── Requests/
│   │       │   └── Resources/
│   │       ├── Models/
│   │       │   ├── CartItem.php
│   │       │   ├── Shipment.php
│   │       │   └── PaymentTransaction.php
│   │       ├── Services/
│   │       │   ├── KbzPayService.php
│   │       │   ├── WaveMoneyService.php
│   │       │   └── OnlineOrderService.php
│   │       ├── Providers/
│   │       │   └── ECommerceServiceProvider.php
│   │       ├── routes.php
│   │       └── tests/
│   │
│   ├── Providers/                         # Global app providers (module registration)
│   └── Exceptions/
│       └── Handler.php
│
├── config/
│   └── modules.php                        # Module enable/disable config
│
├── database/
│   └── migrations/                        # Only global migrations here
│       ├── 0001_01_01_000001_create_cache_table.php
│       └── 0001_01_01_000002_create_jobs_table.php
│
└── routes/
    ├── api.php                            # Master route file → delegates to modules
    └── console.php
```

### Route File Architecture

```php
// routes/api.php — Master route file
// Each module registers its own routes in its routes.php
// The module's ServiceProvider loads them with appropriate prefix/middleware

// Core routes (no auth, public)
Route::prefix('api')->group(function () {
    // Each module registers:
    // Route::prefix('v1')->middleware(['api'])->group(fn() => require $module->routes());
});
```

Each module's ServiceProvider registers its routes:

```php
// Modules/Catalog/Providers/CatalogServiceProvider.php
class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
```

---

## 6. Multi-Store Data Model

### The `stores` Table

```sql
CREATE TABLE stores (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) NOT NULL UNIQUE,  -- 'clothing', 'electronics', 'home-appliances'
    domain          VARCHAR(255) NULL UNIQUE,       -- Custom domain (null = use subdomain)
    description     TEXT NULL,
    logo            VARCHAR(255) NULL,
    contact_email   VARCHAR(255) NULL,
    currency        VARCHAR(3) DEFAULT 'MMK',
    is_active       BOOLEAN DEFAULT TRUE,
    settings        JSON NULL,                      -- Store-specific config (theme, shipping, payment methods)
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

### Models That Get `store_id`

| Current Table | New Column | Rationale |
|--------------|------------|-----------|
| `products` | `store_id` FK NOT NULL | Product belongs to one store |
| `categories` | `store_id` FK NOT NULL | Categories are per-store |
| `product_variants` | (inherits via product) | Already scoped via product |
| `orders` | `store_id` FK NOT NULL | Order placed in a specific store |
| `customers` | `store_id` FK NULL | Customer can be cross-store or per-store |
| `discounts` | `store_id` FK NOT NULL | Promotions are per-store |
| `suppliers` | `store_id` FK NOT NULL | Suppliers per-store |
| `stock_movements` | (inherits via variant) | Already scoped |
| `cash_sessions` | `store_id` FK NOT NULL | Cash per-store |
| `users` | — | Users are global (admin across all stores) |

### Store Resolution

Each API request is scoped to a store via:

1. **Header**: `X-Store: clothing` (used by storefronts)
2. **Subdomain**: `clothing.simpcommerce.local` (optional)
3. **Default**: POS dashboard uses a "default" store or user's assigned store

Middleware resolves the store and makes it available:

```php
// Modules/Store/Http/Middleware/ResolveStore.php
class ResolveStore
{
    public function handle(Request $request, Closure $next)
    {
        $slug = $request->header('X-Store')
              ?? $request->user()?->store?->slug
              ?? 'default';

        $store = Store::where('slug', $slug)->firstOrFail();

        // Make available globally
        app()->instance('current_store', $store);

        return $next($request);
    }
}
```

Global scope for all store-scoped queries:

```php
// Modules/Catalog/Models/Product.php
class Product extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            $builder->where('store_id', app('current_store')->id);
        });
    }
}
```

> **Alternative**: Pass store_id explicitly in queries rather than global scopes — more explicit, easier to test. Global scopes can cause surprising bugs.

---

## 7. Module Registration & Autoloading

### Composer Autoloading

```json
{
    "autoload": {
        "psr-4": {
            "App\\Modules\\Identity\\": "app/Modules/Identity/",
            "App\\Modules\\Catalog\\": "app/Modules/Catalog/",
            "App\\Modules\\Sales\\": "app/Modules/Sales/",
            "App\\Modules\\Store\\": "app/Modules/Store/",
            "App\\Modules\\Customer\\": "app/Modules/Customer/",
            "App\\Modules\\Inventory\\": "app/Modules/Inventory/",
            "App\\Modules\\Promotion\\": "app/Modules/Promotion/",
            "App\\Modules\\Supplier\\": "app/Modules/Supplier/",
            "App\\Modules\\Cash\\": "app/Modules/Cash/",
            "App\\Modules\\Audit\\": "app/Modules/Audit/",
            "App\\Modules\\Report\\": "app/Modules/Report/",
            "App\\Modules\\System\\": "app/Modules/System/",
            "App\\Modules\\ECommerce\\": "app/Modules/ECommerce/"
        }
    }
}
```

### Service Provider Registration

```php
// config/app.php (providers array)
'providers' => [
    // ...
    App\Modules\Identity\Providers\IdentityServiceProvider::class,
    App\Modules\Store\Providers\StoreServiceProvider::class,
    App\Modules\Catalog\Providers\CatalogServiceProvider::class,
    App\Modules\Customer\Providers\CustomerServiceProvider::class,
    App\Modules\Sales\Providers\SalesServiceProvider::class,
    App\Modules\Inventory\Providers\InventoryServiceProvider::class,
    App\Modules\Promotion\Providers\PromotionServiceProvider::class,
    App\Modules\Supplier\Providers\SupplierServiceProvider::class,
    App\Modules\Cash\Providers\CashServiceProvider::class,
    App\Modules\Audit\Providers\AuditServiceProvider::class,
    App\Modules\Report\Providers\ReportServiceProvider::class,
    App\Modules\System\Providers\SystemServiceProvider::class,
    // ECommerce loaded only if enabled
    App\Modules\ECommerce\Providers\ECommerceServiceProvider::class,
],
```

### Optional: Module Config

```php
// config/modules.php
return [
    'enabled' => [
        'ecommerce' => env('MODULE_ECOMMERCE', true),
        'pos'       => env('MODULE_POS', true),
    ],
];
```

---

## 8. Cross-Module Communication

### Rules

1. **No direct model access across modules** — Module A cannot import Module B's models
2. **Communication via contracts/interfaces** — Defined in the consuming module
3. **Event-driven** — Laravel events for cross-module concerns (e.g., `OrderPlaced` → Inventory decrements stock)
4. **Service facades** — Thin wrapper classes for common cross-module operations

### Example: Sales → Inventory Communication

```php
// Modules/Sales/Contracts/InventoryManager.php (interface in Sales module)
interface InventoryManager
{
    public function reserveStock(int $variantId, int $quantity): void;
    public function releaseStock(int $variantId, int $quantity): void;
    public function deductStock(int $variantId, int $quantity): void;
}

// Modules/Inventory/Services/InventoryManagerImpl.php (implementation in Inventory module)
class InventoryManagerImpl implements InventoryManager
{
    public function reserveStock(int $variantId, int $quantity): void
    {
        StockMovement::create([
            'product_variant_id' => $variantId,
            'quantity_change' => -$quantity,
            'reason' => 'reserved',
            // ...
        ]);
    }
    // ...
}

// In InventoryServiceProvider:
$this->app->bind(InventoryManager::class, InventoryManagerImpl::class);

// Sales module uses the interface:
class OrderService
{
    public function __construct(
        private InventoryManager $inventory
    ) {}
}
```

### Example: Event-Driven Stock Deduction

```php
// Modules/Sales/Events/OrderPlaced.php
class OrderPlaced
{
    public function __construct(public Order $order) {}
}

// Modules/Inventory/Listeners/DeductStock.php
class DeductStock
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->order->items as $item) {
            $item->variant->decrement('stock_quantity', $item->quantity);
        }
    }
}

// Registered in InventoryServiceProvider:
Event::listen(OrderPlaced::class, DeductStock::class);
```

---

## 9. API Route Architecture

### Route Prefixes

| Prefix | Purpose | Module | Auth |
|--------|---------|--------|------|
| `/api/auth/*` | Staff login/logout | Identity | Public + Sanctum |
| `/api/profile` | Staff profile | Identity | Sanctum |
| `/api/users/*` | Staff CRUD | Identity | Sanctum + Admin |
| `/api/dashboard/*` | Dashboard summary | Report | Sanctum |
| `/api/products/*` | Product management | Catalog | Sanctum (admin for write) |
| `/api/variants/*` | Variant management | Catalog | Sanctum |
| `/api/categories/*` | Category management | Catalog | Sanctum (admin for write) |
| `/api/customers/*` | Customer CRM | Customer | Sanctum |
| `/api/orders/*` | Order management (POS + online) | Sales | Sanctum |
| `/api/invoices/*` | Invoice management | Sales | Sanctum |
| `/api/discounts/*` | Discount management | Promotion | Sanctum (admin for write) |
| `/api/suppliers/*` | Supplier management | Supplier | Sanctum (admin for write) |
| `/api/cash-sessions/*` | Cash drawer | Cash | Sanctum |
| `/api/stock-movements/*` | Stock history | Inventory | Sanctum (admin) |
| `/api/backups/*` | Database backup | System | Sanctum (admin) |
| `/api/audit-logs/*` | Activity log | Audit | Sanctum (admin) |
| `/api/reports/*` | Analytics | Report | Sanctum |
| `/api/stores/*` | Store management | Store | Sanctum (admin) |
| `/api/public/*` | Storefront catalog | Catalog | Public (no auth) |
| `/api/customer/*` | Customer auth/profile | Customer | Public + Sanctum (customer) |
| `/api/cart/*` | Shopping cart | ECommerce | Sanctum (customer) |
| `/api/checkout/*` | Checkout | ECommerce | Sanctum (customer) |
| `/api/payments/*` | Payment gateway | ECommerce | Mixed (public for webhooks) |
| `/api/my/*` | Customer order management | ECommerce | Sanctum (customer) |

### Route Registration

Each module registers its own routes. Example:

```php
// Modules/Catalog/routes.php
use Illuminate\Support\Facades\Route;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\PublicProductController;

// Admin/Dashboard routes (auth: sanctum)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store'])->middleware('admin');
    // ...
});

// Public routes (no auth)
Route::prefix('public')->group(function () {
    Route::get('products', [PublicProductController::class, 'index']);
    Route::get('products/{slug}', [PublicProductController::class, 'show']);
});
```

---

## 10. Multi-Storefront API Design

### Storefront Identification

Each storefront sends its identity with every request:

```
GET /api/public/products
X-Store: clothing                   # Which store
X-Storefront-Name: simppos-clothing  # Optional: for analytics/logging
Authorization: Bearer <token>       # If customer is logged in
```

The `ResolveStore` middleware processes the header and scopes the entire request.

### Per-Store Configuration

Stores have a `settings` JSON column with storefront-specific config:

```json
{
    "theme": {
        "primary_color": "#1d4ed8",
        "logo": "/storage/store-logos/clothing-logo.png"
    },
    "payment_methods": ["cod", "kbz_pay", "wave_money"],
    "shipping": {
        "methods": [
            { "name": "Standard", "fee": 3000, "days": "3-5" },
            { "name": "Express", "fee": 5000, "days": "1-2" }
        ]
    },
    "currency": "MMK",
    "locale": "my",
    "seo": {
        "title": "Fashion Clothing Store",
        "description": "Best fashion in Myanmar"
    }
}
```

### API Response Envelope

```json
{
    "store": {
        "id": 1,
        "slug": "clothing",
        "name": "Fashion Store",
        "logo_url": "https://..."
    },
    "data": { ... },
    "meta": { "current_page": 1, "total": 42 }
}
```

---

## 11. Migration Path

### Strategy: Incremental, Module by Module

Do NOT attempt to move everything at once. The migration is done in phases, each module independently. The system remains functional after each phase.

```
Phase 0: Establish module structure + Core module
Phase 1: Migrate Identity module (auth, users)
Phase 2: Migrate Catalog module (products, variants, categories)
Phase 3: Migrate Customer module
Phase 4: Migrate Sales module (orders, invoices)
Phase 5: Migrate Inventory, Promotion, Supplier modules
Phase 6: Migrate Cash, Audit, Report, System modules
Phase 7: Add Store module + multi-store scoping
Phase 8: Build ECommerce module
```

### Phase 0 — Foundation

1. Create `app/Modules/` directory structure
2. Move `Core` traits and enums (shared code, no models)
3. Configure PSR-4 autoloading for `App\Modules\*`
4. Create a testing pattern for modules
5. Verify all existing tests still pass

### Phase 1-6 — Module Migration (same pattern for each)

For each module:

1. Create module directory structure
2. Copy files from flat `app/` into module (Models, Controllers, Requests, Resources)
3. Update namespaces from `App\Http\Controllers\Api` → `App\Modules\Catalog\Http\Controllers`
4. Create ServiceProvider with route + migration loading
5. Create module-level `routes.php`
6. Update `composer.json` PSR-4
7. Run `composer dump-autoload`
8. Update route references in `routes/api.php` (remove old, keep new)
9. Run tests
10. Delete old files

### Phase 7 — Multi-Store

1. Create `stores` migration and `Store` model
2. Create `ResolveStore` middleware
3. Add `store_id` columns to all scoped tables (migrations)
4. Update all models with store scoping
5. Create default store for existing data
6. Backfill `store_id` on existing records
7. Update all controllers to be store-aware
8. Test

### Phase 8 — E-Commerce Module

1. Add migrations for new tables (cart_items, shipments, payment_transactions)
2. Create models, services, controllers
3. Implement storefront-facing public API
4. Implement customer auth + cart + checkout
5. Implement payment gateways
6. Test

---

## 12. Directory Scaffold (Starting Point)

```
app/Modules/
├── Core/
│   ├── Traits/
│   │   ├── .gitkeep
│   │   └── (ApiResponse.php, QueryFilter.php will be moved here)
│   ├── Enums/
│   │   └── .gitkeep
│   └── Helpers/
│       └── .gitkeep
├── Identity/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   └── Models/
│       └── .gitkeep
├── Catalog/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   ├── Models/
│   │   └── .gitkeep
│   └── Services/
│       └── .gitkeep
├── Customer/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   └── Models/
│       └── .gitkeep
├── Sales/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   ├── Models/
│   │   └── .gitkeep
│   └── Services/
│       └── .gitkeep
├── Inventory/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   └── Models/
│       └── .gitkeep
├── Promotion/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   ├── Models/
│   │   └── .gitkeep
│   └── Services/
│       └── .gitkeep
├── Supplier/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   └── Models/
│       └── .gitkeep
├── Cash/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   └── Models/
│       └── .gitkeep
├── Audit/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   └── Models/
│       └── .gitkeep
├── Report/
│   ├── Http/Controllers/
│   │   └── .gitkeep
│   └── Services/
│       └── .gitkeep
├── System/
│   └── Http/Controllers/
│       └── .gitkeep
└── ECommerce/
    ├── Http/Controllers/
    │   └── .gitkeep
    ├── Models/
    │   └── .gitkeep
    └── Services/
        └── .gitkeep
```

---

## 13. Testing Strategy

### Module-Level Tests

Each module has its own test directory. Tests use the same `ApiTestCase` base class.

```
Modules/Catalog/tests/
├── ProductTest.php
├── CategoryTest.php
└── VariantTest.php
```

### Cross-Module Integration Tests

```bash
tests/Feature/Integration/
├── OrderInventoryTest.php    # Order → Stock deduction
├── CustomerOrderTest.php     # Customer → Order history
└── StoreCatalogTest.php      # Store → Product scoping
```

### Test Commands

```bash
# Run all tests
php artisan test

# Run specific module
php artisan test app/Modules/Catalog/tests

# Run integration tests
php artisan test tests/Feature/Integration
```

---

## 14. Risks & Considerations

| Risk | Mitigation |
|------|-----------|
| **Migration fatigue** — moving 20k+ lines of code is tedious | Automate via refactoring scripts; do module by module; keep old files until module is verified |
| **Namespace conflicts** — existing code references `App\Models\Product` | Keep old models as aliases during transition; update imports gradually |
| **Global store scoping** — can introduce bugs if forgotten | Middleware + explicit `store_id` parameter (not global scopes); thorough testing |
| **Over-engineering** — modules may not need full separation | Start simple: just directory organization + namespaces. Add ServiceProviders and contracts only when cross-module communication actually happens. |
| **Performance** — event listeners for cross-module communication | Events are synchronous by default — fast enough for monolith. Move to queue only if needed. |

---

## 15. Next Steps

1. ✅ **Decide on project rename** (SimpCommerce vs others)
2. **Phase 0**: Create module scaffold directories + Core module
3. **Phase 1**: Migrate Identity module
4. **Phase 2**: Migrate Catalog module
5. Continue phase by phase...

Each phase is a separate commit, and the system remains functional after each one. The `arch/modular-monolith` branch will hold all migration work until complete, then merged into `master`.
