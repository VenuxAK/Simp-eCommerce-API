# SimpCommerce — Modular Monolith Architecture

> **Status**: Complete — All phases (0–8) implemented
> **Branch**: `arch/modular-monolith` (active development)
> **Migration**: 100% — All modules migrated from flat structure

**Repositories**:
- `simpcommerce-api` — Laravel API backend (this repo)
- `simpcommerce-dashboard` — Vue 3 dashboard SPA (separate repo)

Storefronts will be built as separate repos in a later phase.

---

## 1. Motivation

The current codebase (`SimpCommerce`) was built as a straightforward monolithic Laravel app with a flat directory structure. While this worked for a single POS + one storefront, the system now needs to support:

- **Multiple storefronts** — clothing, electronics, home appliances, each with their own public website
- **Multiple sales channels** — POS (in-store), online storefronts, future channels (WhatsApp, Facebook Shop)
- **Clearer domain boundaries** — developers need to understand and modify specific business areas without touching unrelated code

A **Modular Monolith** gives us clean separation within a single deployable unit — no microservices complexity, no network overhead, but the same disciplined boundaries you'd find in a distributed system.

---

## 2. Vision: Unified Commerce Platform

```
┌────────────────────────────────────────────────ᢌ
│             simpcommerce-api                    │
│           (Laravel Modular Monolith)            │
│                                                  │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ │
│  │Catalog│ │Sales │ │ Iden-│ │Store │ │Cus-  │ │
│  │Module │ │Module│ │tity  │ │Module│ │tomer │ │
│  └──────┘ └──────┘ └Module│ └──────┘ │Module│ │
│  ┌──────┐ ┌──────┐ └──────┘ ┌──────┐ └──────┘ │
│  │Inven-│ │Promo-│ ┌──────┐ │System│ ┌──────┐ │
│  │tory  │ │tion  │ │Audit │ │Module│ │Report│ │
│  │Module│ │Module│ │Module│ └──────┘ │Module│ │
│  └──────┘ └──────┘ └──────┘         └──────┘ │
│                    ┌──────────┐                │
│                    │  Core/   │                │
│                    │  Shared  │                │
│                    │  Kernel  │                │
│                    └──────────┘                │
└──────────────────────┬──────────────────────────ᢖ
                       │ REST API
          ┌────────────┼────────────┐
          ▼            ▼            ▼
  ┌────────────┐ ┌────────────┐ ┌──────────────────┐
  │  Dashboard  │ │ Storefront │ │ Storefront       │
  │ (staff/admin)│ │ (Clothing) │ │ (Electronics)    │
  │ Vue 3 SPA   │ │ Nuxt 3 SSR │ │ Nuxt 3 SSR       │
  │ separate    │ │ future     │ │ future           │
  │ repo        │ │ phase      │ │ phase            │
  └────────────┘ └────────────┘ └──────────────────┘
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

## 3. Project Rename: SimpPOS → SimpCommerce

The project has been renamed to **SimpCommerce** to reflect its evolution from a simple POS into a multi-storefront commerce platform.

### What Changed

| Artifact | Before | After |
|----------|--------|-------|
| Name | SimpPOS | SimpCommerce |
| API repo | SimpPOS/api | simpcommerce-api |
| Dashboard repo | SimpPOS/frontend | simpcommerce-dashboard |
| App name in .env | SimpPOS | SimpCommerce |
| Frontend title | SimpPOS | SimpCommerce |
| All documentation | SimpPOS | SimpCommerce |

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

### Target Module Layout (Within `simpcommerce-api`)

```
simpcommerce-api/
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
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── ProfileController.php
│   │   │   ├── Http/Middleware/AdminMiddleware.php
│   │   │   ├── Http/Requests/
│   │   │   ├── Http/Resources/UserResource.php
│   │   │   ├── Models/User.php
│   │   │   ├── Providers/IdentityServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Store/                         # Multi-Store Management
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/StoreController.php
│   │   │   ├── Http/Middleware/ResolveStore.php
│   │   │   ├── Http/Resources/StoreResource.php
│   │   │   ├── Models/Store.php
│   │   │   ├── Providers/StoreServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Catalog/                       # Products, Categories, Variants
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── ProductVariantController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── PublicProductController.php  # (storefront-facing)
│   │   │   │   └── PublicCategoryController.php
│   │   │   ├── Http/Requests/
│   │   │   ├── Http/Resources/
│   │   │   ├── Models/
│   │   │   │   ├── Product.php
│   │   │   │   ├── Category.php
│   │   │   │   └── ProductVariant.php
│   │   │   ├── Services/
│   │   │   │   ├── ProductImportService.php
│   │   │   │   ├── ProductExportService.php
│   │   │   │   └── MediaService.php
│   │   │   ├── Providers/CatalogServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Customer/                      # Customers, Addresses
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/
│   │   │   │   ├── CustomerController.php
│   │   │   │   └── CustomerAuthController.php
│   │   │   ├── Http/Requests/
│   │   │   ├── Http/Resources/
│   │   │   ├── Models/
│   │   │   │   ├── Customer.php
│   │   │   │   └── Address.php
│   │   │   ├── Providers/CustomerServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Sales/                         # Orders, Invoices, Payments
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/
│   │   │   │   ├── OrderController.php
│   │   │   │   └── InvoiceController.php
│   │   │   ├── Http/Requests/
│   │   │   ├── Http/Resources/
│   │   │   ├── Models/
│   │   │   │   ├── Order.php
│   │   │   │   ├── OrderItem.php
│   │   │   │   ├── Payment.php
│   │   │   │   └── Invoice.php
│   │   │   ├── Services/
│   │   │   │   ├── OrderService.php
│   │   │   │   └── InvoiceNumberGenerator.php
│   │   │   ├── Providers/SalesServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Inventory/                     # Stock, Warehouses
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/StockMovementController.php
│   │   │   ├── Http/Resources/
│   │   │   ├── Models/StockMovement.php
│   │   │   ├── Providers/InventoryServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Promotion/                     # Discounts
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/DiscountController.php
│   │   │   ├── Http/Resources/
│   │   │   ├── Models/Discount.php
│   │   │   ├── Services/DiscountService.php
│   │   │   ├── Providers/PromotionServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Supplier/                      # Vendors
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/SupplierController.php
│   │   │   ├── Http/Resources/
│   │   │   ├── Models/Supplier.php
│   │   │   ├── Providers/SupplierServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Cash/                          # Cash Drawer Sessions
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/CashSessionController.php
│   │   │   ├── Http/Resources/
│   │   │   ├── Models/CashSession.php
│   │   │   ├── Providers/CashServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Audit/                         # Activity Logging
│   │   │   ├── Database/Migrations/
│   │   │   ├── Http/Controllers/AuditLogController.php
│   │   │   ├── Http/Resources/
│   │   │   ├── Models/AuditLog.php
│   │   │   ├── Providers/AuditServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── Report/                        # Analytics & Dashboard
│   │   │   ├── Http/Controllers/
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── Http/Resources/
│   │   │   ├── Services/ReportService.php
│   │   │   ├── Providers/ReportServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   ├── System/                        # Backups, Config
│   │   │   ├── Http/Controllers/BackupController.php
│   │   │   ├── Providers/SystemServiceProvider.php
│   │   │   ├── routes.php
│   │   │   └── tests/
│   │   │
│   │   └── ECommerce/                     # Cart, Checkout, Payments (future)
│   │       ├── Database/Migrations/
│   │       ├── Http/Controllers/
│   │       ├── Models/
│   │       ├── Services/
│   │       ├── Providers/ECommerceServiceProvider.php
│   │       ├── routes.php
│   │       └── tests/
│   │
│   └── ... (existing app/ files remain during migration)
│
├── config/
│   └── modules.php                        # Module enable/disable config
│
├── database/migrations/                   # Only non-module migrations
├── routes/
│   ├── api.php                            # Master route file → delegates to modules
│   └── console.php
├── ARCHITECTURE.md
└── tests/                                 # Global integration tests
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

All 8 phases have been completed. The migration was done incrementally, module by module, with the system remaining functional after each phase.

```
Phase 0: ✅ Establish module structure + Core module
Phase 1: ✅ Migrate Identity module (auth, users)
Phase 2: ✅ Migrate Catalog module (products, variants, categories)
Phase 3: ✅ Migrate Customer module (CRM + auth)
Phase 4: ✅ Migrate Sales module (orders, invoices)
Phase 5: ✅ Migrate Inventory, Promotion, Supplier modules
Phase 6: ✅ Migrate Cash, Audit, Report, System modules
Phase 7: ✅ Add Store module + multi-store scoping
Phase 8: ✅ Build ECommerce module (cart, checkout, shipments)
       ──────────────────────────────────
       (Storefront development in separate Nuxt repos)
       ──────────────────────────────────
```

---

## 12. Directory Scaffold (Current State — Committed)

The module directory structure is already in place on the `arch/modular-monolith` branch:

```
app/Modules/
├── Core/          # Traits/, Enums/, Helpers/
├── Identity/      # Controllers/, Models/, Providers/, tests/
├── Store/         # Controllers/, Middleware/, Models/, Providers/, tests/
├── Catalog/       # Controllers/, Models/, Services/, Providers/, tests/
├── Customer/      # Controllers/, Models/, Providers/, tests/
├── Sales/         # Controllers/, Models/, Services/, Providers/, tests/
├── Inventory/     # Controllers/, Models/, Providers/, tests/
├── Promotion/     # Controllers/, Models/, Services/, Providers/, tests/
├── Supplier/      # Controllers/, Models/, Providers/, tests/
├── Cash/          # Controllers/, Models/, Providers/, tests/
├── Audit/         # Controllers/, Models/, Providers/, tests/
├── Report/        # Controllers/, Services/, Providers/, tests/
├── System/        # Controllers/, Providers/, tests/
└── ECommerce/     # Controllers/, Models/, Services/, Providers/, tests/
```

Each directory contains all subfolders (Http/Controllers/, Http/Requests/, Http/Resources/, Database/Migrations/, Models/, Providers/, Services/, tests/) with `.gitkeep` files. Ready for Phase 0.

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

## 15. Current Status & Next Steps

### ✅ Completed — All 8 Phases

- [x] **Project renamed**: SimpPOS → SimpCommerce (across api/ and dashboard/ repos)
- [x] **Separate repos**: `simpcommerce-api` and `simpcommerce-dashboard` independent
- [x] **Architecture plan**: Written in this document
- [x] **Module scaffold**: 14 module directories created with subfolder structure
- [x] **Phase 0**: Core traits + enums moved, PSR-4 autoloading, base patterns
- [x] **Phase 1**: Identity module (auth, users, profile, AdminMiddleware)
- [x] **Phase 2**: Catalog module (products, variants, categories, media)
- [x] **Phase 3**: Customer module (CRM + auth with Sanctum customer guard)
- [x] **Phase 4**: Sales module (orders, invoices, payments, OrderService)
- [x] **Phase 5**: Inventory, Promotion, Supplier modules
- [x] **Phase 6**: Cash, Audit, Report, System modules
- [x] **Phase 7**: Store module + multi-store scoping (nullable store_id on 6 tables)
- [x] **Phase 8**: ECommerce module (Cart, Checkout, Shipments, Customer Orders)
- [x] **Routes decomposed**: 14 per-module route files loaded from 22-line master
- [x] **136 backend tests**: All passing

### ⏳ Next Steps

1. **Frontend Dashboard**: Integrate online order management into existing Sales pages (source filter, shipment display, mark shipped/delivered actions)
2. **Nuxt Storefronts**: Build separate Nuxt 3 SSR repos for customer-facing storefronts (future phase)
