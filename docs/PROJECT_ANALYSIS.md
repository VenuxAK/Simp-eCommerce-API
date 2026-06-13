# SimpCommerce — Project Analysis

> **Generated**: 2026-06-11  
> **Scope**: Full codebase analysis of `simpcommerce-api` (Laravel 13, PHP 8.4, PostgreSQL 16+)

---

## 1. Project Purpose

**SimpCommerce** is a modular commerce platform designed for small-to-medium businesses. It provides a complete retail operating system with:

- **In-store POS** (Point of Sale) for cash-and-carry sales
- **Multi-storefront e-commerce** with public product catalogs served via Nuxt SSR frontends
- **Customer self-service portal** (cart, COD checkout, order tracking, wishlist, OAuth)
- **Staff dashboard** for inventory, orders, CRM, reporting, and administration
- **Multi-tenant architecture** — a single backend powers multiple independent storefronts

The project is built as a **Laravel Modular Monolith**: 14 domain modules within a single deployable application, each with its own controllers, services, repositories, models, routes, and tests.

---

## 2. Major Modules

### 2.1 Core (`app/Modules/Core/`)
Shared kernel used by all other modules. Contains no HTTP layer — purely infrastructure.

| Component | Purpose |
|-----------|---------|
| `Enums/` (11 enums) | Strongly-typed PHP 8.1+ backed enums for all domain constants |
| `Traits/ApiResponse` | Standardized `{ data }` / `{ message }` JSON response helpers |
| `Traits/QueryFilter` | Reusable Eloquent scope for search/date/status filtering |
| `Traits/StoreScope` | Canonical `resolveStoreId()` for multi-tenant store resolution |
| `Traits/AuthorizesOwnership` | Ownership guard for customer-owned resources |
| `Traits/HandlesPasswordUpdate` | Shared password hash-on-update logic |
| `Repositories/Repository` | Base repository class with common Eloquent query methods |

### 2.2 Identity (`app/Modules/Identity/`)
Staff authentication, user management, and profile management.

- **Models**: `User`
- **Controllers**: `AuthController`, `UserController`, `ProfileController`
- **Middleware**: `CachedTokenAuth`, `RoleMiddleware`
- **Routes**: Staff login/logout/me, profile CRUD, user CRUD (root-only)
- **Auth Guard**: `sanctum` (Bearer tokens, 24h lifetime)

### 2.3 Store (`app/Modules/Store/`)
Multi-tenant store management.

- **Models**: `Store`
- **Controllers**: `StoreController` (root-only CRUD)
- **Middleware**: `ResolveStore` — reads `X-Store` header, resolves store by slug
- **Routes**: Store CRUD (root-only)

### 2.4 Catalog (`app/Modules/Catalog/`)
Product catalog management — the core inventory domain.

- **Models**: `Product`, `ProductVariant`, `Category`, `Brand`
- **Controllers**: `ProductController`, `ProductVariantController`, `CategoryController`, `BrandController`, `StorefrontController`
- **Services**: `ProductService`, `ProductImportService`, `ProductExportService`, `MediaService`, `StorefrontService`, `StorefrontCacheService`
- **Jobs**: `ProcessProductImportJob`
- **Routes**: Products CRUD + CSV + images + labels, Variants (stock/images/SKU), Categories CRUD + image, Brands CRUD + logo, Storefront (public catalog)

### 2.5 Customer (`app/Modules/Customer/`)
Customer CRM, authentication, and profile management.

- **Models**: `Customer` (Authenticatable), `Address`
- **Controllers**: `CustomerController`, `CustomerAuthController`, `CustomerProfileController`, `AddressController`, `OAuthController`
- **Auth Guard**: `customer` (session-based for OAuth, token-based for API — 7d lifetime)
- **Routes**: Customer CRUD (staff), Customer register/login/logout, OAuth redirect/callback, Profile, Address book

### 2.6 Sales (`app/Modules/Sales/`)
Order and invoice management — POS sales + online order fulfillment.

- **Models**: `Order`, `OrderItem`, `Payment`, `Invoice`
- **Controllers**: `OrderController`, `InvoiceController`
- **Services**: `OrderService`, `InvoiceService`, `InvoiceNumberGenerator`
- **Routes**: Orders list/create (POS)/status transitions/returns, Invoices list/detail/PDF/print/receipt

### 2.7 ECommerce (`app/Modules/ECommerce/`)
Customer-facing online shopping experience.

- **Models**: `CartItem`, `WishlistItem`, `Shipment`
- **Controllers**: `CartController`, `CheckoutController`, `MyOrderController`, `WishlistController`
- **Services**: `CartService`, `WishlistService`, `OnlineOrderService`, `MyOrderService`
- **Repositories**: `CartItemRepository`, `WishlistItemRepository`, `ShipmentRepository`
- **Routes**: Cart CRUD, Checkout (place + validate), My Orders (history + cancel), Wishlist (toggle + CRUD)

### 2.8 Inventory (`app/Modules/Inventory/`)
Stock movement tracking and audit trail.

- **Models**: `StockMovement`
- **Controllers**: `StockMovementController` (admin-only list)
- **Services**: `StockService`
- **Routes**: Filtered stock movement list (by date, reason)

### 2.9 Promotion (`app/Modules/Promotion/`)
Discount management system.

- **Models**: `Discount`
- **Controllers**: `DiscountController`
- **Services**: `DiscountService`
- **Routes**: Discounts CRUD + active list (admin write, staff read)

### 2.10 Supplier (`app/Modules/Supplier/`)
Supplier/vendor management.

- **Models**: `Supplier`
- **Controllers**: `SupplierController`
- **Routes**: Suppliers CRUD (admin write, staff read)

### 2.11 Cash (`app/Modules/Cash/`)
Cash register session management for POS operations.

- **Models**: `CashSession`
- **Controllers**: `CashSessionController`
- **Services**: `CashSessionService`
- **Routes**: Cash sessions history, active session, open/close

### 2.12 Audit (`app/Modules/Audit/`)
Activity logging for compliance and debugging.

- **Models**: `AuditLog`
- **Controllers**: `AuditLogController` (root-only)
- **Routes**: Filtered audit log list (by action)

### 2.13 Report (`app/Modules/Report/`)
Business intelligence and analytics.

- **Controllers**: `DashboardController`, `ReportController`
- **Services**: `DashboardService`, `ReportService`
- **Routes**: Dashboard summary, sales reports, best-sellers, payment method breakdowns

### 2.14 System (`app/Modules/System/`)
System operations and maintenance.

- **Controllers**: `BackupController` (root-only)
- **Services**: `BackupService`
- **Jobs**: `CreateBackupJob`
- **Routes**: Backup create/list/download

---

## 3. User Roles

### 3.1 Staff Roles (Dashboard)

| Role | Enum Value | Capabilities |
|------|-----------|--------------|
| **Root** | `UserRole::Root` | Full system access — user CRUD, store CRUD, audit logs, backups, all admin writes |
| **Store Admin** | `UserRole::StoreAdmin` | Admin operations within a store — create/update/delete products, categories, brands, discounts, suppliers, manage orders, approve returns |
| **Staff** | `UserRole::Staff` | Read access + POS operations — browse catalog, create POS orders, stock adjustments, cash sessions, view reports |

Role enforcement via `RoleMiddleware` with route-level role declarations (e.g., `RoleMiddleware:root,store_admin`).

### 3.2 Customer Roles

| Role | Auth Method | Capabilities |
|------|------------|--------------|
| **Customer** | Sanctum token (7d) or Google OAuth session | Register, login, profile, addresses, cart, COD checkout, order history, order cancellation, wishlist |
| **Guest** | None | Browse storefront products/categories/brands/settings |

---

## 4. Database Entities

### 4.1 Core Tables (20 domain tables + 3 Laravel standard)

| Table | Primary Key | Key Columns | Store-Scoped |
|-------|-------------|-------------|--------------|
| `users` | `id` | `name`, `email`, `password`, `role` (enum), `store_id` (FK) | Yes (FK) |
| `stores` | `id` | `name`, `slug` (unique), `domain`, `description`, `logo`, `phone`, `email`, `is_active`, `settings` (JSON) | N/A |
| `categories` | `id` | `name`, `slug`, `description`, `store_id` (FK), `parent_id` (FK), `image` | Yes |
| `brands` | `id` | `name`, `slug`, `logo`, `store_id` (FK) | Yes |
| `products` | `id` | `category_id` (FK), `brand_id` (FK), `supplier_id` (FK), `store_id` (FK), `name`, `slug`, `description`, `base_price`, `image` | Yes |
| `product_variants` | `id` | `product_id` (FK), `sku` (unique), `size`, `color`, `image`, `price_adjustment`, `purchase_price`, `stock_quantity` | No |
| `customers` | `id` | `name`, `email` (unique nullable), `phone`, `address`, `loyalty_points`, `password` (nullable), `store_id` (FK) | Yes |
| `addresses` | `id` | `customer_id` (FK), `type` (enum), `name`, `phone`, `street`, `city`, `state`, `postal_code`, `is_default` | No |
| `orders` | `id` | `user_id` (FK nullable), `customer_id` (FK nullable), `store_id` (FK), `order_number` (unique), `total_amount`, `source` (enum), `status` (enum), `notes` | Yes |
| `order_items` | `id` | `order_id` (FK), `product_variant_id` (FK), `quantity`, `unit_price`, `subtotal` | No |
| `payments` | `id` | `order_id` (FK unique), `method` (enum), `amount`, `paid_at` | No |
| `invoices` | `id` | `order_id` (FK unique), `invoice_number` (unique), `issued_date`, `due_date`, `status` (enum), `notes`, `terms` | No |
| `discounts` | `id` | `name`, `type` (enum), `value`, `applies_to` (enum), `category_id` (FK nullable), `product_id` (FK nullable), `store_id` (FK), `starts_at`, `ends_at`, `is_active` | Yes |
| `stock_movements` | `id` | `product_variant_id` (FK), `quantity_change`, `reason` (enum), `reference_type`, `reference_id`, `user_id` (FK nullable) | No |
| `suppliers` | `id` | `name`, `contact_person`, `phone`, `email`, `address`, `notes`, `store_id` (FK) | Yes |
| `cash_sessions` | `id` | `user_id` (FK), `store_id` (FK), `opened_at`, `closed_at`, `opening_balance`, `closing_balance`, `expected_balance`, `difference`, `notes` | Yes |
| `cart_items` | `id` | `customer_id` (FK), `product_variant_id` (FK), `quantity` | No |
| `wishlist_items` | `id` | `customer_id` (FK), `product_variant_id` (FK) | No |
| `shipments` | `id` | `order_id` (FK), `address_id` (FK), `method` (enum), `tracking_number`, `tracking_url`, `shipped_at`, `delivered_at`, `notes` | No |
| `audit_logs` | `id` | `user_id` (FK nullable), `action` (enum), `model_type`, `model_id`, `old_values`, `new_values`, `ip_address` | No |

### 4.2 Key Entity Relationships

```
Store ──1:N──> Product, Category, Brand, Order, Discount, Supplier, CashSession, Customer, User
Product ──1:N──> ProductVariant
ProductVariant ──1:N──> OrderItem, StockMovement, CartItem, WishlistItem
Customer ──1:N──> Order, Address, CartItem, WishlistItem
Order ──1:1──> Payment, Invoice
Order ──1:1──> Shipment
Order ──1:N──> OrderItem
Category ──1:N──> Product
Category ──0:N──> Category (self-referencing parent)
Brand ──1:N──> Product
Supplier ──1:N──> Product
```

---

## 5. Business Workflows

### 5.1 POS Checkout (In-Store Sale)
```
Staff opens Cash Session (opening_balance)
  → Browse product grid / search
  → Select variant (SKU), enter quantity
  → Optionally select customer
  → Optionally apply active discount
  → Enter payment details (cash/transfer) + amount received
  → Complete sale
  → Stock automatically deducted
  → Order created (source=pos, status=completed)
  → Invoice auto-generated
  → StockMovement logged (reason=sale)
  → Staff closes Cash Session (closing_balance, difference calculated)
```

### 5.2 Online COD Checkout (Customer)
```
Customer authenticated (token or OAuth session)
  → Browse storefront, view product detail
  → Add to cart (server-side, stock-validated)
  → Manage cart quantities
  → Select or create shipping address
  → Validate stock (GET /checkout/validate)
  → Place order (POST /checkout) — atomic transaction:
      - Re-verify stock with row lock
      - Create Order (source=online, status=processing)
      - Create OrderItems
      - Deduct stock, log StockMovements (reason=sale)
      - Create Invoice (status=issued, due 30 days)
      - Create Shipment (method=standard, linked to address)
      - Clear cart
  → Order appears in /my/orders
```

### 5.3 Order Fulfillment (Online)
```
Staff dashboard:
  → View order (status=processing)
  → PATCH /orders/{id}/status to 'shipped'
  → Set shipment tracking/tracking_url/shipped_at
  → PATCH /orders/{id}/status to 'delivered'
  → Set shipment delivered_at
Valid transitions: processing → shipped → delivered
```

### 5.4 Order Cancellation (Customer)
```
POST /my/orders/{id}/cancel
  → Validates order status is 'processing'
  → Restores stock quantities (StockMovement reason=return)
  → Sets order status → cancelled
  → Sets invoice status → cancelled
  → Idempotent: double-cancel guard prevents double restock
```

### 5.5 OAuth Login (Google)
```
Customer clicks "Sign in with Google"
  → Frontend calls GET /auth/oauth/google/redirect
  → Receives { redirect_url }
  → Opens redirect_url in browser
  → User consents on Google
  → Google redirects to /auth/oauth/google/callback?code=...
  → Backend exchanges code for Google user info
  → Finds or creates Customer by email
  → Creates session cookie
  → Redirects to storefront
Note: OAuth customers have password=null, cannot use email/password login.
```

### 5.6 Multi-Store Data Flow
```
Nuxt storefront (e.g., clothing store):
  → NUXT_PUBLIC_STORE_SLUG=clothing
  → Sends X-Store: clothing header on every API request
  → ResolveStore middleware:
      - Reads header
      - Store::where('slug', $slug)->firstOrFail()
      - Sets app('current_store')
  → All catalog queries use StoreScope::resolveStoreId()
  → Products, categories, brands filtered by store_id
  → Customer registrations tagged with store_id
  → Orders tagged with store_id at checkout
```

### 5.7 CSV Product Import/Export
```
Import:
  → Staff uploads CSV via POST /products/import/csv
  → ProductImportService validates rows (name, price, SKU format)
  → ProcessProductImportJob dispatched to queue
  → Products + variants created in batch

Export:
  → GET /products/export/csv
  → ProductExportService generates CSV with headers
  → Downloads all products in current store scope
```

### 5.8 Return/Refund Processing
```
POST /orders/{id}/return (admin only)
  → Specify items: [{ variant_id, quantity }]
  → Restock variants
  → Log StockMovement (reason=return)
  → Create refund Payment record
  → Update order status (completed → refunded)
```

---

## 6. API Modules

### 6.1 Authentication

| Module | Endpoints | Auth | Roles |
|--------|-----------|------|-------|
| Staff Auth | `POST /auth/login`, `POST /auth/logout`, `GET /auth/me` | Public / Staff | All staff |
| Customer Auth | `POST /customer/register`, `POST /customer/login`, `POST /customer/logout` | Public / Customer | Customers |
| OAuth | `GET /auth/oauth/{provider}/redirect`, `GET /auth/oauth/{provider}/callback` | Public | Customers (Google) |

### 6.2 Staff Dashboard

| Module | Base Path | Methods | Write Access |
|--------|-----------|---------|--------------|
| Profile | `/profile` | GET, PUT | Self |
| Users | `/users` | CRUD | Root only |
| Products | `/products` | CRUD + CSV + image + labels | Admin (root, store_admin) |
| Variants | `/variants` | PATCH stock, POST image, GET by-sku | Staff |
| Categories | `/categories` | CRUD + image | Admin write, staff read |
| Brands | `/brands` | CRUD + logo | Admin write, staff read |
| Orders | `/orders` | List, create (POS), show, update status, return | Admin for status/returns |
| Invoices | `/invoices` | List, show, PDF, print, receipt | Staff read |
| Customers | `/customers` | CRUD + orders sub-resource | Admin write (root), staff read |
| Suppliers | `/suppliers` | CRUD | Admin write, staff read |
| Discounts | `/discounts` | CRUD + active list | Admin write, staff read |
| Stock Movements | `/stock-movements` | Filtered list | Admin only |
| Cash Sessions | `/cash-sessions` | List, active, open, close | Staff |
| Stores | `/stores` | CRUD | Root only |
| Backups | `/backups` | Create, list, download | Root only |
| Dashboard | `/dashboard/summary` | GET | Staff |
| Reports | `/reports/*` | Sales, best-sellers, payment-methods | Staff |
| Audit Logs | `/audit-logs` | Filtered list | Root only |

### 6.3 Storefront (Public)

| Endpoint | Description |
|----------|-------------|
| `GET /storefront/products` | Paginated product listing (`?page=&category_id=&search=`) |
| `GET /storefront/products/{slug}` | Product detail with variants |
| `GET /storefront/categories` | Category list with product counts |
| `GET /storefront/brands` | Brand list |
| `GET /storefront/settings` | Store configuration (name, logo, contact, settings JSON) |

### 6.4 Customer Portal

| Module | Endpoints | Methods |
|--------|-----------|---------|
| Profile | `/customer/me`, `/customer/profile` | GET, PUT |
| Addresses | `/addresses`, `/addresses/{id}/default` | CRUD + set default |
| Cart | `/cart`, `/cart/{id}` | GET, POST, PUT, DELETE |
| Checkout | `/checkout`, `/checkout/validate` | POST, GET |
| Orders | `/my/orders`, `/my/orders/{id}`, `/my/orders/{id}/cancel` | GET, POST |
| Wishlist | `/wishlist`, `/wishlist/toggle`, `/wishlist/{id}` | GET, POST, DELETE |

---

## 7. Technical Architecture Summary

| Aspect | Implementation |
|--------|---------------|
| **Framework** | Laravel 13 (PHP 8.4) |
| **Database** | PostgreSQL 16+ (production), SQLite in-memory (tests) |
| **Auth** | Sanctum token-based + Session cookies; two guards (`api` for staff, `customer` for customers) |
| **OAuth** | Laravel Socialite v5 (Google provider) |
| **Queue** | Database driver (jobs table) |
| **Rate Limiting** | 10 req/min for auth endpoints, 60 req/min for API, 10 req/min for checkout |
| **Concurrency** | Idempotency key middleware on checkout, row-level DB locking for stock |
| **File Storage** | Laravel filesystem (local driver, symlink to public) |
| **PDF** | barryvdh/laravel-dompdf for invoice PDFs |
| **Code Style** | Laravel Pint |
| **Testing** | PHPUnit v12 (147 tests across 20 test files) |
| **Architecture** | Modular monolith with domain-driven module boundaries |
| **Frontend** | Vue 3 + TypeScript + Shadcn/vue (dashboard), Nuxt 4 SSR (storefronts) |

---

## 8. Missing Documentation

### 8.1 Critical Gaps

| Area | What's Missing | Priority |
|------|---------------|----------|
| **Deployment Guide** | No documented deployment process (Laravel Cloud, Docker, server setup, env configuration, queue worker setup, storage configuration) | High |
| **Queue & Jobs** | `ProcessProductImportJob` and `CreateBackupJob` are undocumented. No guide on running queue workers or handling failed jobs. | High |
| **Environment Variables** | No comprehensive `.env` reference — only `.env.example` exists with defaults; no explanation of all configurable options (CORS origins, queue connection, mail settings for password resets) | High |

### 8.2 Moderate Gaps

| Area | What's Missing | Priority |
|------|---------------|----------|
| **Brand Module** | Brands were added (migration `2026_06_10_111459`) but are not documented in `SPECIFICATION.md` or `ARCHITECTURE.md` | Medium |
| **Category Hierarchy** | `parent_id` self-referencing FK on categories (migration `2026_06_10_111500`) is not documented — no explanation of tree structure, nesting depth limits, or how the storefront handles hierarchical categories | Medium |
| **i18n / Translation** | README mentions "English + Burmese server-side error translations" but no documentation on how translations are structured, where files live, or how to add new locales | Medium |
| **CORS Configuration** | No documentation on how to configure CORS for Nuxt storefront origins (different domains) | Medium |
| **Payment Methods** | Only `Cash` and `Transfer` are implemented; KBZ Pay and Wave Money are "deferred." No roadmap or integration guide. | Medium |
| **Stock Management** | No documentation on how `purchase_price` vs `price_adjustment` vs `base_price` interact, or the difference between absolute stock set (PATCH `/variants/{id}/stock`) and transactional stock operations (checkout). | Medium |

### 8.3 Low Priority Gaps

| Area | What's Missing | Priority |
|------|---------------|----------|
| **API Versioning** | No versioning strategy — endpoints are unversioned (`/api/products`, not `/api/v1/products`) | Low |
| **Testing Strategy** | No guide on how to write new tests, test conventions, or coverage expectations | Low |
| **Performance / Scaling** | No documentation on caching (StorefrontCacheService exists but is undocumented), query optimization, or scaling considerations | Low |
| **Monitoring / Logging** | Nightwatch is installed but no documentation on monitoring setup, log levels, or alerting | Low |
| **Changelog** | No changelog or release process documentation | Low |
| **Database Partitioning** | Migration `2026_06_09_001321_partition_audit_logs_and_orders_table.php` adds partitioning — undocumented rationale and maintenance | Low |
| **Scaling Indexes** | Migration `2026_06_08_000001_add_scaling_indexes.php` adds performance indexes — undocumented | Low |

### 8.4 Documentation Already Present

| Document | Coverage |
|----------|----------|
| `API.md` (383 lines) | Complete API reference for all 100+ endpoints, route groups, auth, response formats, error codes |
| `ARCHITECTURE.md` (329 lines) | Modular monolith design, module map, multi-store model, service layer, directory structure |
| `SPECIFICATION.md` (313 lines) | Tech stack, full DB schema, enum values, key workflows, security model, testing coverage |
| `README.md` (171 lines) | Quick start, credentials, module list, key features, directory structure |

---

## 9. Codebase Health Indicators

| Metric | Value |
|--------|-------|
| PHP files in `app/` | 171 |
| Modules | 14 |
| Route files | 15 |
| Registered routes | 103 |
| Domain enums | 11 |
| Database migrations | 37 |
| Model factories | 15+ |
| Test files | 20 |
| Test assertions | 147 (all passing) |
| Service classes | 14 |
| Repository classes | 15 |
| Job classes | 2 |
| Controllers | 28 |

The overall architecture is well-structured with clear separation of concerns, consistent naming conventions, and comprehensive test coverage. The primary documentation gap is operational (deployment, queues, env config) rather than architectural.
