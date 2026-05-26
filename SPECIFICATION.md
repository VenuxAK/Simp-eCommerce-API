# SimpCommerce — Project Specification

## 1. Overview

**SimpCommerce** is a modular commerce platform for small-to-medium businesses with bilingual support (English / Burmese), multi-storefront e-commerce capability, and production-ready features. It includes a full POS module for in-store sales alongside customer-facing online shopping (cart, COD checkout, order management), all backed by a single API.

- **Backend**: Laravel 13 REST API (modular monolith, 14 modules) — PostgreSQL
- **Dashboard**: Vue 3 + TypeScript + Shadcn/vue SPA (staff/admin operations)
- **Storefronts**: Nuxt 4 SSR (separate repos per store, consuming public API)
- **Auth**: Sanctum token-based (24h staff, 7d customer); storefront planned for HttpOnly cookie switch
- **Database**: PostgreSQL 16+ (development/production), SQLite in-memory (tests)

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 13 |
| Auth | Sanctum (token-based) — two guards: `web` (staff) and `customer` |
| Database | PostgreSQL 16+ (SQLite in-memory for tests) |
| Dashboard Frontend | Vue 3 + Composition API + TypeScript + Vite 8 |
| UI Library | Shadcn/vue (Tailwind-based) |
| State Management | Pinia |
| Charts | Chart.js + vue-chartjs |
| PDF | barryvdh/laravel-dompdf |
| Styling | Tailwind CSS v4 |
| i18n | vue-i18n (English + Burmese) |
| Tests | PHPUnit (147 tests) |

---

## 3. Database Schema

### Tables & Fields

| Table | Fields |
|---|---|
| **users** | `id`, `name`, `email`, `password`, `role` (admin/staff), `remember_token`, `timestamps` |
| **stores** | `id`, `name`, `slug` (unique), `domain` (nullable), `description`, `logo` (nullable), `phone` (nullable), `email` (nullable), `is_active`, `settings` (JSON), `timestamps` |
| **categories** | `id`, `name`, `slug`, `description`, `store_id` (FK nullable), `timestamps` |
| **products** | `id`, `category_id` (FK), `supplier_id` (FK nullable), `store_id` (FK nullable), `name`, `slug`, `description`, `base_price`, `image`, `timestamps` |
| **product_variants** | `id`, `product_id` (FK), `sku` (unique), `size`, `color`, `image`, `price_adjustment`, `purchase_price`, `stock_quantity`, `timestamps` |
| **customers** | `id`, `name`, `email` (nullable unique), `phone`, `address`, `loyalty_points`, `password` (nullable), `email_verified_at`, `remember_token`, `timestamps` |
| **addresses** | `id`, `customer_id` (FK), `type`, `name`, `phone`, `street`, `city`, `state`, `postal_code`, `is_default`, `timestamps` |
| **orders** | `id`, `user_id` (FK nullable), `customer_id` (FK nullable), `store_id` (FK nullable), `order_number` (unique), `total_amount`, `source` (pos/online), `status`, `notes`, `timestamps` |
| **order_items** | `id`, `order_id` (FK), `product_variant_id` (FK), `quantity`, `unit_price`, `subtotal`, `timestamps` |
| **payments** | `id`, `order_id` (FK), `method` (cash/transfer), `amount`, `paid_at`, `timestamps` |
| **invoices** | `id`, `order_id` (FK unique), `invoice_number` (unique), `issued_date`, `due_date`, `status`, `notes`, `terms`, `timestamps` |
| **discounts** | `id`, `name`, `type` (percentage/fixed), `value`, `applies_to`, `category_id` (FK nullable), `product_id` (FK nullable), `store_id` (FK nullable), `starts_at`, `ends_at`, `is_active`, `timestamps` |
| **stock_movements** | `id`, `product_variant_id` (FK), `quantity_change`, `reason`, `reference_type`, `reference_id`, `user_id` (FK nullable), `timestamps` |
| **suppliers** | `id`, `name`, `contact_person`, `phone`, `email`, `address`, `notes`, `store_id` (FK nullable), `timestamps` |
| **cash_sessions** | `id`, `user_id` (FK), `store_id` (FK nullable), `opened_at`, `closed_at`, `opening_balance`, `closing_balance`, `expected_balance`, `difference`, `notes`, `timestamps` |
| **cart_items** | `id`, `customer_id` (FK), `session_id` (nullable UUID), `product_variant_id` (FK), `quantity`, `timestamps` |
| **shipments** | `id`, `order_id` (FK), `address_id` (FK), `method`, `tracking_number`, `tracking_url`, `shipped_at`, `delivered_at`, `notes`, `timestamps` |
| **audit_logs** | `id`, `user_id` (FK nullable), `action`, `model_type`, `model_id`, `old_values`, `new_values`, `ip_address`, `timestamps` |

### Key Relationships

```
Store         ──1:N──> Product, Category, Order, Discount, Supplier, CashSession
Product       ──1:N──> ProductVariant
ProductVariant ──1:N──> OrderItem, StockMovement, CartItem
Customer      ──1:N──> Order, Address, CartItem
Order         ──1:1──> Payment, Invoice, Shipment
```

---

## 4. API Endpoints (Complete Map)

### Storefront (Public — no auth)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/storefront/products?page=&category_id=&search=` | Paginated product listing (store-scoped) |
| GET | `/api/storefront/products/{slug}` | Product detail with variants |
| GET | `/api/storefront/categories` | Category list with product counts |
| GET | `/api/storefront/settings` | Store config (name, logo, description, settings) |

### Staff Auth (Public)

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/login` | Staff login (rate-limited: 10/min) |
| POST | `/api/auth/logout` | Revoke current token |
| GET | `/api/auth/me` | Current staff user |

### Customer Auth (Public)

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/customer/register` | Register (rate-limited: 10/min) |
| POST | `/api/customer/login` | Login (rate-limited: 10/min) |

### Customer Portal (auth:customer)

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/customer/logout` | Revoke current token |
| GET | `/api/customer/me` | View own profile |
| PUT | `/api/customer/profile` | Update profile |
| GET/POST/PUT/DELETE | `/api/addresses` | Address book CRUD |
| PUT | `/api/addresses/{id}/default` | Set default address |
| GET/POST/PUT/DELETE | `/api/cart` | Shopping cart CRUD |
| DELETE | `/api/cart` | Clear cart |
| POST | `/api/checkout` | Place COD order |
| GET | `/api/checkout/validate` | Validate stock before checkout |
| GET | `/api/my/orders` | Order history (paginated) |
| GET | `/api/my/orders/{id}` | Order detail with shipment |
| POST | `/api/my/orders/{id}/cancel` | Cancel order (processing only) |

### Staff Dashboard (auth:sanctum)

| Module | Endpoints (prefix) | Auth |
|---|---|---|
| Categories | `/api/categories` | CRUD (write=admin) |
| Products | `/api/products` | CRUD + CSV import/export + images + labels (write=admin) |
| Variants | `/api/variants` | Stock adjustment, images, barcode lookup |
| Customers (CRM) | `/api/customers` | CRUD + order history (write=admin) |
| Orders | `/api/orders` | List/detail/create, status update (admin), returns (admin) |
| Invoices | `/api/invoices` | List/detail/print/pdf/receipt |
| Suppliers | `/api/suppliers` | CRUD (write=admin) |
| Discounts | `/api/discounts` | CRUD + active list (write=admin) |
| Stock Movements | `/api/stock-movements` | List with filters (admin) |
| Cash Sessions | `/api/cash-sessions` | List/active/open/close |
| Stores | `/api/stores` | CRUD (admin) |
| Backups | `/api/backups` | Create/list/download (admin) |
| Reports | `/api/dashboard/summary`, `/api/reports/*` | Dashboard + sales/best-sellers/payment-methods |
| Users | `/api/users` | CRUD (admin) |
| Audit Logs | `/api/audit-logs` | List with filters (admin) |
| Profile | `/api/profile` | Self-update |

---

## 5. Route Architecture

```
routes/api.php (master loader)
├── Public (no auth)
│   └── auth.php → login, register
├── Storefront (store middleware)
│   └── storefront.php → /products, /products/{slug}, /categories, /settings
├── Customer (auth:customer)
│   └── customer-portal.php → addresses, cart, checkout, my/orders, customer/*
└── Staff (auth:sanctum)
    ├── identity.php     → auth/logout, me, profile, users
    ├── catalog.php      → products, variants, categories
    ├── sales.php        → orders, invoices
    ├── customer.php     → customers CRM
    ├── report.php       → dashboard, reports
    ├── promotion.php    → discounts
    ├── supplier.php     → suppliers
    ├── cash.php         → cash-sessions
    ├── inventory.php    → stock-movements
    ├── system.php       → backups
    ├── audit.php        → audit-logs
    └── store.php        → stores CRUD
```

---

## 6. Key Workflows

### POS Checkout
```
Product Grid → Variant Dialog → Add to Cart → Select Customer (optional)
→ Select Discount (optional) → Enter Payment → Complete Sale
→ Stock deducted, Order created (status=completed), Invoice auto-generated
```

### Online COD Checkout
```
Customer logged in → Cart with items → Select/Create Address → Place Order
→ Stock deducted, Order created (status=processing, source=online)
→ Shipment record created → Staff marks Shipped → Staff marks Delivered
```

### Multi-Store Data Flow
```
Nuxt storefront → X-Store: clothing header
→ ResolveStore middleware → looks up Store by slug
→ scopes all catalog queries by store_id
→ returns only that store's products, categories, etc.
```

---

## 7. Security & Validation

- **Auth**: Sanctum token-based (Bearer tokens), two guards: `web` (staff) + `customer`
- **Token lifetimes**: 24h staff, 7d customers; old tokens revoked on login
- **Rate limiting**: Login throttled to 10/min; API general throttle 60/min
- **Role-based**: Admin middleware for sensitive endpoints
- **Status transitions**: POS (pending→completed→cancelled→refunded), Online (processing→shipped→delivered, processing→cancelled)
- **Password policy**: Min 8 chars, uppercase + lowercase + digit
- **Stock validation**: Atomic queries, idempotent double-cancel guard
- **Input validation**: FormRequest classes for all endpoints
- **Backup security**: basename() strips path traversal on download

---

## 8. Testing

- **147 backend tests** across 20 test files (PHPUnit)
- Run with: `cd api && php artisan test`
- Coverage: Auth, Products, Variants, Categories, Customers, Orders, Invoices, Discounts, Suppliers, Stock Movements, Cash Sessions, Returns, Reports, Dashboard, Users, Profile, Backups, **Storefront**

---

## 9. Multi-Store Architecture

| Table | Has `store_id` | Scoped in Storefront? |
|---|---|---|
| `products` | Yes (nullable) | ✅ `->where('store_id', ...)` |
| `categories` | Yes (nullable) | ✅ `->where('store_id', ...)` |
| `orders` | Yes (nullable) | ✅ Set at checkout |
| `discounts` | Yes (nullable) | ⏳ Not yet |
| `suppliers` | Yes (nullable) | ⏳ Not yet |
| `cash_sessions` | Yes (nullable) | Staff only |

Store scoping via **opt-in middleware** (`ResolveStore`) — no global scopes.

---

## 10. ECommerce Module (Implemented)

| Component | Status | Details |
|---|---|---|
| Cart | ✅ | Server-side, stock-validated, per-authenticated-customer |
| COD Checkout | ✅ | Transactional (order + invoice + shipment + stock deduction + cart clear) |
| Order Cancellation | ✅ | Processing only, restocks items |
| Shipments | ✅ | Tracks address, method, shipped/delivered timestamps |
| Order Sources | ✅ | `source` field: `pos` or `online` |
| Payment Gateways | ⏳ Deferred | KBZ Pay, Wave Money (future phase) |

---

## 11. Numbering Conventions

| Entity | Format | Example |
|---|---|---|
| Order Number | `ORD-{YYYYMMDD}-{XXXX}` | ORD-20260526-0001 |
| Invoice Number | `INV-{YYYYMMDD}-{XXXX}` | INV-20260526-0001 |

Sequential per date, resets daily. Implemented in `InvoiceNumberGenerator` with DB-level locking.
