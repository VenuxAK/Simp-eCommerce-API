# SimpCommerce API Reference

> Complete API documentation for the dashboard SPA and storefront clients.
> **103 registered routes** across 15 module route files.

---

## Base URL

```
http://localhost:8000/api
```

## Authentication

| Guard            | Method                       | Lifetime | Used By               |
|------------------|------------------------------|----------|-----------------------|
| `auth:sanctum`   | Sanctum token (Bearer)       | 24 hours | Staff dashboard       |
| `auth:customer`  | Sanctum token (Bearer)       | 7 days   | Customer portal       |
| OAuth (Google)   | Socialite → Sanctum token    | 7 days   | Customer portal       |

**Staff login** returns `{ token, user }`. Attach via `Authorization: Bearer <token>`.

**Customer login** returns `{ token, customer }`. Same pattern.

**OAuth (Google)** returns a redirect URL; after consent, the callback exchanges the code for `{ token, customer }`.

## Multi-Store

Storefront and customer portal endpoints require an `X-Store` header identifying the store by slug. The `ResolveStore` middleware reads this header and scopes all catalog queries. Defaults to `main` if omitted.

```
X-Store: clothing
```

## Rate Limiting

- Login / register endpoints: **10 requests per minute**
- All other endpoints: **60 requests per minute**

## Response Format

```json
{ "data": { ... } }

{ "data": [...], "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 95 } }

{ "message": "..." }
```

---

## Part A — Staff Dashboard Endpoints

All require `Authorization: Bearer <staff-token>` unless marked Public.

### A.1 Auth

| Method | Endpoint       | Role   | Body                    | Response         |
|--------|----------------|--------|-------------------------|------------------|
| POST   | `/auth/login`  | Public | `{ email, password }`   | `{ token, user }` |
| POST   | `/auth/logout` | Staff  | —                       | `{ message }`    |
| GET    | `/auth/me`     | Staff  | —                       | `{ data: User }` |

### A.2 Profile

| Method | Endpoint   | Role  | Body                          | Response         |
|--------|------------|-------|-------------------------------|------------------|
| GET    | `/profile` | Staff | —                             | `{ data: User }` |
| PUT    | `/profile` | Staff | `{ name, email, password? }` | `{ data: User }` |

### A.3 User Management

| Method | Endpoint      | Role  | Description                                           |
|--------|---------------|-------|-------------------------------------------------------|
| GET    | `/users`      | Admin | List staff users (paginated)                          |
| POST   | `/users`      | Admin | Create staff user `{ name, email, password, role }`  |
| GET    | `/users/{id}` | Admin | Show staff user                                       |
| PUT    | `/users/{id}` | Admin | Update staff user                                     |
| DELETE | `/users/{id}` | Admin | Delete staff user (cannot delete self)                |

**Roles**: `root`, `store_admin`, `staff` (backed by `UserRole` enum).

### A.4 Products

| Method | Endpoint                    | Role  | Body / Notes                                                                             |
|--------|-----------------------------|-------|------------------------------------------------------------------------------------------|
| GET    | `/products`                 | Staff | `?page=&search=&category_id=` — paginated                                               |
| GET    | `/products/{id}`            | Staff | Single product with variants                                                             |
| POST   | `/products`                 | Admin | `{ category_id, name, base_price, description?, supplier_id?, variants: [...] }`        |
| PUT    | `/products/{id}`            | Admin | Update product fields                                                                    |
| DELETE | `/products/{id}`            | Admin | Delete (fails if referenced by orders)                                                   |
| POST   | `/products/{id}/image`      | Staff | Multipart `image` file upload                                                            |
| GET    | `/products/export/csv`      | Staff | Download products as CSV                                                                 |
| POST   | `/products/import/csv`      | Admin | Multipart `file` CSV upload                                                              |
| GET    | `/products/{id}/labels`     | Staff | HTML barcode label page                                                                  |

### A.5 Product Variants

| Method | Endpoint                      | Role  | Body / Notes                       |
|--------|-------------------------------|-------|------------------------------------|
| PATCH  | `/variants/{id}/stock`        | Staff | `{ quantity }` — absolute stock set |
| POST   | `/variants/{id}/image`        | Staff | Multipart `image` file upload       |
| GET    | `/variants/by-sku/{sku}`      | Staff | Barcode lookup by SKU               |

### A.6 Categories

| Method | Endpoint             | Role  | Body                         |
|--------|----------------------|-------|------------------------------|
| GET    | `/categories`        | Staff | List all                     |
| GET    | `/categories/{id}`   | Staff | Single                       |
| POST   | `/categories`        | Admin | `{ name, description? }`     |
| PUT    | `/categories/{id}`   | Admin | Update                       |
| DELETE | `/categories/{id}`   | Admin | Delete (fails if has products) |

### A.7 Orders & Sales

| Method | Endpoint                    | Role  | Body / Notes                                                                    |
|--------|-----------------------------|-------|---------------------------------------------------------------------------------|
| GET    | `/orders`                   | Staff | `?page=&search=&status=` — paginated                                           |
| GET    | `/orders/{id}`              | Staff | Full detail with items, payment, invoice, shipment                              |
| POST   | `/orders`                   | Staff | POS: `{ customer_id?, items: [{ product_variant_id, quantity }], payment_method, amount_received?, notes? }` |
| PATCH  | `/orders/{id}/status`       | Admin | `{ status }` — valid transitions enforced                                       |
| POST   | `/orders/{id}/return`       | Admin | `{ items: [{ variant_id, quantity }] }` — item-level return with restock        |

### A.8 Invoices

| Method | Endpoint                      | Role  | Notes               |
|--------|-------------------------------|-------|---------------------|
| GET    | `/invoices`                   | Staff | `?page=&search=&status=` |
| GET    | `/invoices/{id}`              | Staff | Full detail with order |
| GET    | `/invoices/{id}/print`        | Staff | Print view          |
| GET    | `/invoices/{id}/pdf`          | Staff | Download PDF        |
| GET    | `/invoices/{id}/receipt`      | Staff | Thermal receipt     |

### A.9 Customers (CRM)

| Method | Endpoint                        | Role  | Body / Notes                         |
|--------|---------------------------------|-------|--------------------------------------|
| GET    | `/customers`                    | Staff | `?page=&search=`                    |
| GET    | `/customers/{id}`               | Staff | Single with order count              |
| GET    | `/customers/{id}/orders`        | Staff | Order history                        |
| POST   | `/customers`                    | Staff | `{ name, email?, phone?, address? }` |
| PUT    | `/customers/{id}`               | Admin | Update                               |
| DELETE | `/customers/{id}`               | Admin | Delete                               |

### A.10 Suppliers

| Method | Endpoint           | Role  | Body                                                        |
|--------|--------------------|-------|-------------------------------------------------------------|
| GET    | `/suppliers`       | Staff | `?page=`                                                   |
| GET    | `/suppliers/{id}`  | Staff | Single                                                      |
| POST   | `/suppliers`       | Admin | `{ name, contact_person?, phone?, email?, address?, notes? }` |
| PUT    | `/suppliers/{id}`  | Admin | Update                                                      |
| DELETE | `/suppliers/{id}`  | Admin | Delete (fails if referenced by products)                    |

### A.11 Discounts

| Method | Endpoint               | Role  | Body / Notes                                                                              |
|--------|------------------------|-------|-------------------------------------------------------------------------------------------|
| GET    | `/discounts`           | Staff | `?page=`                                                                                 |
| GET    | `/discounts/{id}`      | Staff | Single                                                                                    |
| GET    | `/discounts/active`    | Staff | Active discounts for POS                                                                  |
| POST   | `/discounts`           | Admin | `{ name, type: percentage\|fixed, value, applies_to: all\|category\|product, category_id?, product_id?, starts_at?, ends_at?, is_active }` |
| PUT    | `/discounts/{id}`      | Admin | Update                                                                                    |
| DELETE | `/discounts/{id}`      | Admin | Delete                                                                                    |

### A.12 Stock Movements

| Method | Endpoint           | Role  | Notes                                          |
|--------|--------------------|-------|------------------------------------------------|
| GET    | `/stock-movements` | Admin | `?page=&date_from=&date_to=&reason=` — paginated |

### A.13 Cash Sessions

| Method | Endpoint                    | Role  | Body / Notes           |
|--------|-----------------------------|-------|------------------------|
| GET    | `/cash-sessions`            | Staff | Session history        |
| GET    | `/cash-sessions/active`     | Staff | Current open session   |
| POST   | `/cash-sessions/open`       | Staff | `{ opening_balance }`  |
| POST   | `/cash-sessions/close`      | Staff | `{ closing_balance, notes? }` |

### A.14 Stores

| Method | Endpoint        | Role  | Body / Notes                                                                    |
|--------|-----------------|-------|---------------------------------------------------------------------------------|
| GET    | `/stores`       | Admin | List all                                                                        |
| POST   | `/stores`       | Admin | `{ name, slug, domain?, description?, logo?, phone?, email?, is_active?, settings? }` |
| GET    | `/stores/{id}`  | Admin | Single                                                                          |
| PUT    | `/stores/{id}`  | Admin | Update                                                                          |
| DELETE | `/stores/{id}`  | Admin | Cannot delete default (`main`) store                                            |

### A.15 Backups

| Method | Endpoint                         | Role  | Notes                  |
|--------|----------------------------------|-------|------------------------|
| POST   | `/backups`                       | Admin | Create backup          |
| GET    | `/backups`                       | Admin | List backups           |
| GET    | `/backups/{filename}/download`   | Admin | Download backup file   |

### A.16 Reports & Dashboard

| Method | Endpoint                      | Role  | Notes                                             |
|--------|-------------------------------|-------|---------------------------------------------------|
| GET    | `/dashboard/summary`          | Staff | Overview: today's sales, orders, low stock, recent orders |
| GET    | `/reports/sales`              | Staff | `?date_from=&date_to=` — sales with daily breakdown |
| GET    | `/reports/best-sellers`       | Staff | Top products by quantity sold                     |
| GET    | `/reports/payment-methods`    | Staff | Sales breakdown by payment type                   |

### A.17 Audit Logs

| Method | Endpoint       | Role  | Notes                          |
|--------|----------------|-------|--------------------------------|
| GET    | `/audit-logs`  | Admin | `?page=&action=` — paginated   |

---

## Part B — Storefront Endpoints

Requires `X-Store: <slug>` header on all requests.

### B.1 Public Catalog (No Auth)

| Method | Endpoint                          | Query Params                          | Response                    |
|--------|-----------------------------------|---------------------------------------|-----------------------------|
| GET    | `/storefront/products`            | `page`, `per_page`, `category_id`, `search` | `{ data: Product[], meta }` |
| GET    | `/storefront/products/{slug}`     | —                                     | `{ data: Product }`         |
| GET    | `/storefront/categories`          | —                                     | `{ data: Category[] }`      |
| GET    | `/storefront/settings`            | —                                     | `{ data: StoreSettings }`   |

`Product` includes: `id`, `name`, `slug`, `description`, `base_price`, `image_url`, `total_stock`, `category` (nested), `variants[]` (nested with `sku`, `size`, `color`, `price_adjustment`, `stock_quantity`, `image_url`).

`StoreSettings` includes: `id`, `name`, `slug`, `description`, `logo`, `phone`, `email`, `settings` (freeform JSON), `products_count`.

### B.2 Customer Auth (Public)

| Method | Endpoint               | Body                         | Response              |
|--------|------------------------|------------------------------|-----------------------|
| POST   | `/customer/register`   | `{ name, email, password }`  | `{ token, customer }` |
| POST   | `/customer/login`      | `{ email, password }`        | `{ token, customer }` |

Password policy: min 8 chars, must include uppercase, lowercase, and a digit.

### B.3 OAuth — Google (`auth/oauth`)

| Method | Endpoint                         | Notes                                    |
|--------|----------------------------------|------------------------------------------|
| GET    | `/auth/oauth/google/redirect`    | Returns `{ redirect_url }`              |
| GET    | `/auth/oauth/google/callback`    | `?code=…` → `{ token, customer }`      |

**Flow**: Frontend calls redirect endpoint → opens `redirect_url` in browser → user consents → Google redirects back with `?code=` → backend exchanges code, finds or creates `Customer` by email, returns Sanctum token. OAuth customers have `password = null` and can only authenticate via Google.

### B.4 Customer Profile (`auth:customer`)

| Method | Endpoint              | Body                           | Response              |
|--------|-----------------------|--------------------------------|-----------------------|
| POST   | `/customer/logout`    | —                              | `{ message }`         |
| GET    | `/customer/me`        | —                              | `{ data: Customer }`  |
| PUT    | `/customer/profile`   | `{ name, email, password? }` | `{ data: Customer }`  |

### B.5 Address Book (`auth:customer`)

| Method | Endpoint                      | Body                                                                 | Notes                       |
|--------|-------------------------------|----------------------------------------------------------------------|-----------------------------|
| GET    | `/addresses`                  | —                                                                    | List own addresses           |
| POST   | `/addresses`                  | `{ name, phone, street, city, state, postal_code, is_default? }`   | Create                       |
| GET    | `/addresses/{id}`             | —                                                                    | Single                       |
| PUT    | `/addresses/{id}`             | same as create                                                       | Update                       |
| DELETE | `/addresses/{id}`             | —                                                                    | Delete                       |
| PUT    | `/addresses/{id}/default`     | —                                                                    | Set default, unset others    |

### B.6 Shopping Cart (`auth:customer`)

| Method | Endpoint         | Body                                | Response              |
|--------|------------------|-------------------------------------|-----------------------|
| GET    | `/cart`          | —                                   | `{ data: CartItem[] }` |
| POST   | `/cart`          | `{ product_variant_id, quantity }`  | `{ data: CartItem[] }` |
| PUT    | `/cart/{id}`     | `{ quantity }`                      | `{ data: CartItem[] }` |
| DELETE | `/cart/{id}`     | —                                   | `{ data: CartItem[] }` |
| DELETE | `/cart`          | —                                   | `{ data: [] }` — clears all |

`CartItem` includes: `id`, `product_variant_id`, `quantity`, `variant` (nested with `product`).
Price: `unit_price = product.base_price + variant.price_adjustment`.

### B.7 Checkout (`auth:customer`)

| Method | Endpoint              | Body                      | Response          |
|--------|-----------------------|---------------------------|-------------------|
| GET    | `/checkout/validate`  | —                         | `{ data: { valid, warnings: [] } }` |
| POST   | `/checkout`           | `{ address_id, notes? }` | `{ data: Order }` |

**Checkout flow** (COD only — handled by `OnlineOrderService`):
1. Validates cart is non-empty
2. Re-checks stock on each variant (with row lock)
3. Creates `Order` (status=`processing`, source=`online`)
4. Creates `OrderItem` per cart item
5. Deducts stock, logs `StockMovement`
6. Creates `Invoice` (status=`issued`, due in 30 days)
7. Creates `Shipment` (linked to address, method=`standard`)
8. Clears the cart

### B.8 Order History (`auth:customer`)

| Method | Endpoint                       | Notes                             |
|--------|--------------------------------|-----------------------------------|
| GET    | `/my/orders`                   | `?page=` — paginated              |
| GET    | `/my/orders/{id}`              | Full detail with items + shipment |
| POST   | `/my/orders/{id}/cancel`       | Processing orders only; restocks  |

`Order` includes: `order_number`, `total_amount`, `status`, `source`, `created_at`, `items[]` (with `variant` → `product`), `shipment` (with `address`), `invoice`.

### B.9 Wishlist (`auth:customer`)

| Method | Endpoint              | Body                       | Notes                        |
|--------|-----------------------|----------------------------|------------------------------|
| GET    | `/wishlist`           | —                          | List wishlisted items        |
| POST   | `/wishlist/toggle`    | `{ product_variant_id }`   | Add or remove (toggle)       |
| DELETE | `/wishlist/{id}`      | —                          | Remove specific item         |
| DELETE | `/wishlist`           | —                          | Clear entire wishlist        |

---

## Part C — Order Status Reference

### POS Orders (`source: pos`)

| Status      | Description                                             |
|-------------|----------------------------------------------------------|
| `pending`   | Order created, awaiting completion                      |
| `completed` | Sale finalized, stock deducted                         |
| `cancelled` | Cancelled before completion                            |
| `refunded`  | Completed order refunded, stock restored               |

Valid transitions: `pending → completed → cancelled → refunded`

### Online Orders (`source: online`)

| Status        | Description                                                 |
|---------------|--------------------------------------------------------------|
| `processing`  | Order placed, stock deducted, awaiting fulfillment          |
| `shipped`     | Marked shipped by staff (tracking set on shipment)          |
| `delivered`   | Delivered to customer (`shipment.delivered_at` set)         |
| `cancelled`   | Cancelled by customer (processing only; stock restored)     |

Valid transitions: `processing → shipped → delivered`, `processing → cancelled`

---

## Part D — Numbering Conventions

| Entity         | Format                   | Example              | Generator               |
|----------------|--------------------------|----------------------|-------------------------|
| Order Number   | `ORD-{YYYYMMDD}-{XXXX}` | `ORD-20260115-0001`  | `InvoiceNumberGenerator` |
| Invoice Number | `INV-{YYYYMMDD}-{XXXX}` | `INV-20260115-0001`  | `InvoiceNumberGenerator` |

Sequential per date, resets daily. Database-level locking for thread safety.

---

## Part E — Backup

Backups stored in `storage/app/backups/`. Driver auto-detected:

| Driver     | Method                              |
|------------|-------------------------------------|
| PostgreSQL | `pg_dump` CLI                       |
| MySQL      | `mysqldump` CLI                     |
| SQLite     | File copy from `database/database.sqlite` |

Filenames: `backup-2026-05-27-150000.pgsql`. Listing filters by `backup-` prefix. Downloads use `basename()` to prevent path traversal.

---

## Part F — Error Responses

| Status | Format                                    | Example                        |
|--------|-------------------------------------------|--------------------------------|
| 422    | `{ message, errors: { field: [...] } }`   | Validation failed              |
| 401    | `{ message: "Unauthenticated." }`          | Missing or invalid token       |
| 403    | `{ message: "..." }`                       | Insufficient role (admin only) |
| 404    | `{ message: "Resource not found." }`       | Route or model not found       |
| 429    | `{ message: "Too Many Requests" }`         | Rate limit exceeded            |
| 500    | `{ message: "Server Error" }`              | Internal error                 |
