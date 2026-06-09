# SimpCommerce — Project Specification

## 1. Overview

**SimpCommerce** is a modular commerce platform for small-to-medium businesses with bilingual support (English / Burmese), multi-storefront e-commerce capability, and production-ready features. It includes a full POS module for in-store sales alongside customer-facing online shopping (cart, wishlist, COD checkout, order management), all backed by a single API.

- **Backend**: Laravel 13 REST API (modular monolith, 14 modules, 103 routes) — PostgreSQL
- **Dashboard**: Vue 3 + TypeScript + Shadcn/vue SPA (staff/admin operations)
- **Storefronts**: Nuxt 4 SSR (separate repos per store, consuming public API)
- **Auth**: Sanctum token-based (24h staff, 7d customer); OAuth (Google) via Socialite
- **Database**: PostgreSQL 16+ (development/production), SQLite in-memory (tests)

---

## 2. Tech Stack

| Layer               | Technology                                              |
|---------------------|---------------------------------------------------------|
| Backend Framework   | Laravel 13                                              |
| Auth                | Sanctum (token-based) — two guards: `web` (staff) and `customer` |
| OAuth               | Laravel Socialite v5 (Google)                           |
| Database            | PostgreSQL 16+ (SQLite in-memory for tests)             |
| Dashboard Frontend  | Vue 3 + Composition API + TypeScript + Vite             |
| UI Library          | Shadcn/vue (Tailwind-based)                             |
| State Management    | Pinia                                                   |
| Charts              | Chart.js + vue-chartjs                                  |
| PDF                 | barryvdh/laravel-dompdf                                 |
| Styling             | Tailwind CSS v4                                         |
| i18n                | vue-i18n (English + Burmese)                            |
| Tests               | PHPUnit v12 (147 tests)                                 |

---

## 3. Database Schema

### Tables & Fields

| Table                | Fields                                                                                                   |
|----------------------|----------------------------------------------------------------------------------------------------------|
| **users**            | `id`, `name`, `email`, `password`, `role` (root/store_admin/staff), `store_id` (FK), `remember_token`, `timestamps` |
| **stores**           | `id`, `name`, `slug` (unique), `domain` (nullable), `description`, `logo` (nullable), `phone` (nullable), `email` (nullable), `is_active`, `settings` (JSON), `timestamps` |
| **categories**       | `id`, `name`, `slug`, `description`, `store_id` (FK nullable), `timestamps`                             |
| **products**         | `id`, `category_id` (FK), `supplier_id` (FK nullable), `store_id` (FK nullable), `name`, `slug`, `description`, `base_price`, `image`, `timestamps` |
| **product_variants** | `id`, `product_id` (FK), `sku` (unique), `size`, `color`, `image`, `price_adjustment`, `purchase_price`, `stock_quantity`, `timestamps` |
| **customers**        | `id`, `name`, `email` (nullable unique), `phone`, `address`, `loyalty_points`, `password` (nullable), `email_verified_at`, `remember_token`, `store_id` (FK nullable), `timestamps` |
| **addresses**        | `id`, `customer_id` (FK), `type` (AddressType enum), `name`, `phone`, `street`, `city`, `state`, `postal_code`, `is_default`, `timestamps` |
| **orders**           | `id`, `user_id` (FK nullable), `customer_id` (FK nullable), `store_id` (FK nullable), `order_number` (unique), `total_amount`, `source` (OrderSource enum), `status` (OrderStatus enum), `notes`, `timestamps` |
| **order_items**      | `id`, `order_id` (FK), `product_variant_id` (FK), `quantity`, `unit_price`, `subtotal`, `timestamps`    |
| **payments**         | `id`, `order_id` (FK), `method` (PaymentMethod enum), `amount`, `paid_at`, `timestamps`                 |
| **invoices**         | `id`, `order_id` (FK unique), `invoice_number` (unique), `issued_date`, `due_date`, `status` (InvoiceStatus enum), `notes`, `terms`, `timestamps` |
| **discounts**        | `id`, `name`, `type` (DiscountType enum), `value`, `applies_to` (DiscountScope enum), `category_id` (FK nullable), `product_id` (FK nullable), `store_id` (FK nullable), `starts_at`, `ends_at`, `is_active`, `timestamps` |
| **stock_movements**  | `id`, `product_variant_id` (FK), `quantity_change`, `reason` (StockMovementReason enum), `reference_type`, `reference_id`, `user_id` (FK nullable), `timestamps` |
| **suppliers**        | `id`, `name`, `contact_person`, `phone`, `email`, `address`, `notes`, `store_id` (FK nullable), `timestamps` |
| **cash_sessions**    | `id`, `user_id` (FK), `store_id` (FK nullable), `opened_at`, `closed_at`, `opening_balance`, `closing_balance`, `expected_balance`, `difference`, `notes`, `timestamps` |
| **cart_items**       | `id`, `customer_id` (FK), `product_variant_id` (FK), `quantity`, `timestamps`                           |
| **wishlist_items**   | `id`, `customer_id` (FK), `product_variant_id` (FK), `timestamps`                                       |
| **shipments**        | `id`, `order_id` (FK), `address_id` (FK), `method` (ShipmentMethod enum), `tracking_number`, `tracking_url`, `shipped_at`, `delivered_at`, `notes`, `timestamps` |
| **audit_logs**       | `id`, `user_id` (FK nullable), `action` (AuditAction enum), `model_type`, `model_id`, `old_values`, `new_values`, `ip_address`, `timestamps` |

### Key Relationships

```
Store          ──1:N──> Product, Category, Order, Discount, Supplier, CashSession, Customer, User
Product        ──1:N──> ProductVariant
ProductVariant ──1:N──> OrderItem, StockMovement, CartItem, WishlistItem
Customer       ──1:N──> Order, Address, CartItem, WishlistItem
Order          ──1:1──> Payment, Invoice, Shipment
```

---

## 4. Enums (Core Module)

All domain-specific string values are backed by PHP 8.1+ enums in `app/Modules/Core/Enums/`:

| Enum                    | Values                                                          |
|-------------------------|-----------------------------------------------------------------|
| `UserRole`              | `Root`, `StoreAdmin`, `Staff`                                   |
| `OrderStatus`           | `Pending`, `Processing`, `Completed`, `Shipped`, `Delivered`, `Cancelled`, `Refunded` |
| `OrderSource`           | `Pos`, `Online`                                                 |
| `InvoiceStatus`         | `Draft`, `Issued`, `Paid`, `Cancelled`, `Overdue`               |
| `PaymentMethod`         | `Cash`, `Transfer`                                              |
| `DiscountType`          | `Percentage`, `Fixed`                                           |
| `DiscountScope`         | `All`, `Category`, `Product`                                    |
| `StockMovementReason`   | `Sale`, `Purchase`, `Adjustment`, `Return`                      |
| `ShipmentMethod`        | `Cod`, `Standard`, `Express`                                    |
| `AddressType`           | `Shipping`, `Billing`                                           |
| `AuditAction`           | `Created`, `Updated`, `Deleted`                                 |

---

## 5. API Endpoints (Complete Map)

### Storefront (Public — no auth, `X-Store` header required)

| Method | Endpoint                                           | Description                           |
|--------|----------------------------------------------------|---------------------------------------|
| GET    | `/api/storefront/products?page=&category_id=&search=` | Paginated product listing (store-scoped) |
| GET    | `/api/storefront/products/{slug}`                  | Product detail with variants          |
| GET    | `/api/storefront/categories`                       | Category list with product counts     |
| GET    | `/api/storefront/settings`                         | Store config (name, logo, currency)   |

### Staff Auth (Public)

| Method | Endpoint          | Description                        |
|--------|-------------------|------------------------------------|
| POST   | `/api/auth/login` | Staff login (rate-limited: 10/min) |
| POST   | `/api/auth/logout`| Revoke current token               |
| GET    | `/api/auth/me`    | Current staff user                 |

### Customer Auth (Public)

| Method | Endpoint                                    | Description                              |
|--------|---------------------------------------------|------------------------------------------|
| POST   | `/api/customer/register`                    | Register (rate-limited: 10/min)          |
| POST   | `/api/customer/login`                       | Login (rate-limited: 10/min)             |
| GET    | `/api/auth/oauth/{provider}/redirect`       | OAuth redirect URL (Google)              |
| GET    | `/api/auth/oauth/{provider}/callback?code=` | OAuth callback → Redirects with Session Cookie   |

### Customer Portal (`auth:customer`)

| Method           | Endpoint                          | Description                    |
|------------------|-----------------------------------|--------------------------------|
| POST             | `/api/customer/logout`            | Revoke token                   |
| GET              | `/api/customer/me`                | View own profile               |
| PUT              | `/api/customer/profile`           | Update profile                 |
| GET/POST/PUT/DELETE | `/api/addresses`               | Address book CRUD              |
| PUT              | `/api/addresses/{id}/default`     | Set default address            |
| GET/POST/PUT/DELETE | `/api/cart`                    | Shopping cart CRUD             |
| DELETE           | `/api/cart`                       | Clear cart                     |
| POST             | `/api/checkout`                   | Place COD order                |
| GET              | `/api/checkout/validate`          | Validate stock before checkout |
| GET              | `/api/my/orders`                  | Order history (paginated)      |
| GET              | `/api/my/orders/{id}`             | Order detail with shipment     |
| POST             | `/api/my/orders/{id}/cancel`      | Cancel order (processing only) |
| GET              | `/api/wishlist`                   | Wishlist items                 |
| POST             | `/api/wishlist/toggle`            | Add/remove toggle              |
| DELETE           | `/api/wishlist/{id}`              | Remove specific item           |
| DELETE           | `/api/wishlist`                   | Clear entire wishlist          |

### Staff Dashboard (`auth:sanctum`)

| Module               | Endpoints (prefix)       | Notes                          |
|----------------------|--------------------------|--------------------------------|
| Profile              | `/api/profile`           | Self-update                    |
| Users                | `/api/users`             | CRUD (admin)                   |
| Categories           | `/api/categories`        | CRUD (write = admin)           |
| Products             | `/api/products`          | CRUD + CSV + images + labels   |
| Variants             | `/api/variants`          | Stock adj., images, SKU lookup |
| Customers (CRM)      | `/api/customers`         | CRUD + order history           |
| Orders               | `/api/orders`            | List/detail/create/status/return |
| Invoices             | `/api/invoices`          | List/detail/print/pdf/receipt  |
| Suppliers            | `/api/suppliers`         | CRUD (admin)                   |
| Discounts            | `/api/discounts`         | CRUD + active list (admin)     |
| Stock Movements      | `/api/stock-movements`   | Filtered list (admin)          |
| Cash Sessions        | `/api/cash-sessions`     | Open/close/active              |
| Stores               | `/api/stores`            | CRUD (admin)                   |
| Backups              | `/api/backups`           | Create/list/download (admin)   |
| Dashboard            | `/api/dashboard/summary` | Staff overview                 |
| Reports              | `/api/reports/*`         | Sales/best-sellers/payment-methods |
| Audit Logs           | `/api/audit-logs`        | Filtered list (admin)          |

---

## 6. Route Architecture

```
routes/api.php (master loader)
├── Public (no auth)
│   └── auth.php → staff login, customer register/login, OAuth
├── Storefront (store middleware)
│   └── storefront.php → /products, /products/{slug}, /categories, /settings
├── Customer (store + stateful + auth:customer)
│   └── customer-portal.php → addresses, cart, checkout, my/orders, wishlist, customer/*
└── Staff (store + auth:sanctum)
    ├── identity.php      → auth/logout, me, profile, users
    ├── catalog.php       → products, variants, categories
    ├── sales.php         → orders, invoices
    ├── customer.php      → customers CRM
    ├── report.php        → dashboard, reports
    ├── promotion.php     → discounts
    ├── supplier.php      → suppliers
    ├── cash.php          → cash-sessions
    ├── inventory.php     → stock-movements
    ├── system.php        → backups
    ├── audit.php         → audit-logs
    └── store.php         → stores CRUD
```

---

## 7. Key Workflows

### POS Checkout
```
Product Grid → Variant Dialog → Add to Cart → Select Customer (optional)
→ Select Discount (optional) → Enter Payment → Complete Sale
→ Stock deducted, Order created (status=completed, source=pos), Invoice auto-generated
```

### Online COD Checkout
```
Customer logged in → Cart with items → Select/Create Address
→ POST /api/checkout (OnlineOrderService transaction):
  Order created (status=processing, source=online)
  + OrderItems + StockMovements + Invoice + Shipment + Cart cleared
→ Staff marks Shipped → Staff marks Delivered
```

### Order Cancellation (Customer)
```
POST /api/my/orders/{id}/cancel (processing only)
→ Stock restored (StockMovement reason=return)
→ Order status → cancelled, Invoice status → cancelled
```

### Multi-Store Data Flow
```
Nuxt storefront → X-Store: clothing header
→ ResolveStore middleware → looks up Store by slug
→ scopes all catalog queries by store_id
→ returns only that store's products, categories, etc.
```

### OAuth (Google) Flow
```
Frontend → GET /api/auth/oauth/google/redirect → { redirect_url }
→ Open redirect_url in browser → User consents on Google
→ Google redirects to callback with ?code=
→ GET /api/auth/oauth/google/callback
→ OAuthController: exchange code → find or create Customer by email → create session → redirect to storefront
```

---

## 8. Security & Validation

- **Auth**: Sanctum token-based (Bearer tokens), two guards: `web` (staff) + `customer`
- **Token lifetimes**: 24h staff, 7d customers; old tokens revoked on login
- **Rate limiting**: Login/register throttled to 10/min; API general throttle 60/min
- **Role-based**: Three roles (`root`, `store_admin`, `staff`) enforced via `AdminMiddleware`; `UserRole` enum
- **Status transitions**: POS (`pending→completed→cancelled→refunded`), Online (`processing→shipped→delivered`, `processing→cancelled`)
- **Password policy**: Min 8 chars, uppercase + lowercase + digit
- **Stock validation**: Atomic row-lock queries at checkout, idempotent double-cancel guard
- **Input validation**: FormRequest classes for all endpoints
- **Backup security**: `basename()` strips path traversal on download
- **OAuth**: OAuth customers (`password = null`) cannot use password login

---

## 9. Testing

- **147 backend tests** across 20 test files (PHPUnit v12)
- Run with: `php artisan test --compact`
- Coverage: Auth, Products, Variants, Categories, Customers, Orders, Invoices, Discounts, Suppliers, Stock Movements, Cash Sessions, Returns, Reports, Dashboard, Users, Profile, Backups, Storefront, OAuth

---

## 10. Multi-Store Architecture

| Table           | Has `store_id` | Scoped in Storefront? |
|-----------------|----------------|------------------------|
| `products`      | Yes (nullable) | ✅ `->where('store_id', ...)` |
| `categories`    | Yes (nullable) | ✅ `->where('store_id', ...)` |
| `orders`        | Yes (nullable) | ✅ Set at checkout      |
| `customers`     | Yes (nullable) | ✅ Set at register      |
| `discounts`     | Yes (nullable) | ⏳ Not yet              |
| `suppliers`     | Yes (nullable) | ⏳ Not yet              |
| `cash_sessions` | Yes (nullable) | Staff only              |
| `users`         | Yes (FK)       | Staff assignment        |

Store scoping via **opt-in middleware** (`ResolveStore`) — no global scopes. `StoreScope` trait provides a canonical `resolveStoreId()` helper used across services.

---

## 11. ECommerce Module

| Component          | Status | Details                                                         |
|--------------------|--------|-----------------------------------------------------------------|
| Cart               | ✅     | Server-side, stock-validated, per-authenticated-customer        |
| Wishlist           | ✅     | Toggle add/remove, per-authenticated-customer                   |
| COD Checkout       | ✅     | Transactional (order + invoice + shipment + stock deduction + cart clear) |
| Order Cancellation | ✅     | Processing only, restocks items, cancels invoice                |
| Shipments          | ✅     | Tracks address, method, shipped/delivered timestamps            |
| Order Sources      | ✅     | `source` field: `pos` or `online` (backed by `OrderSource` enum) |
| Payment Gateways   | ⏳     | KBZ Pay, Wave Money (future phase)                              |

---

## 12. Numbering Conventions

| Entity         | Format                   | Example              |
|----------------|--------------------------|----------------------|
| Order Number   | `ORD-{YYYYMMDD}-{XXXX}` | ORD-20260526-0001    |
| Invoice Number | `INV-{YYYYMMDD}-{XXXX}` | INV-20260526-0001    |

Sequential per date, resets daily. Implemented in `InvoiceNumberGenerator` with DB-level locking.

---

## 13. Core Shared Infrastructure

Located in `app/Modules/Core/`:

| Component               | Purpose                                                             |
|-------------------------|---------------------------------------------------------------------|
| `Enums/`                | 11 strongly-typed PHP enums for all domain values                   |
| `Traits/ApiResponse`    | Standardized `{ data }` / `{ message }` JSON response helpers       |
| `Traits/QueryFilter`    | Reusable scope for search/date/status filtering on Eloquent queries  |
| `Traits/StoreScope`     | Canonical `resolveStoreId()` for multi-tenant store resolution      |
| `Traits/AuthorizesOwnership` | Ownership guard for customer-owned resources (cart, addresses) |
| `Traits/HandlesPasswordUpdate` | Shared password hash-on-update logic                        |
| `Repositories/Repository` | Base repository class with common Eloquent query methods          |
