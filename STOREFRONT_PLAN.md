# SimpCommerce — Storefront Development Plan

> **Status**: Planning — steps ordered by dependency
> **Target**: Nuxt 3 SSR storefront(s) consuming the same `simpcommerce-api`

---

## Step 1 — Fix Critical Bugs (Blockers)

### Bug 1: `orders.user_id` cannot be null

| File | Line | Problem |
|---|---|---|
| `database/migrations/2026_05_21_083353_create_orders_table.php` | 13 | `user_id` has `constrained()` (NOT NULL) |
| `app/Modules/ECommerce/Services/OnlineOrderService.php` | 51 | Sets `user_id => null` for online orders |

**Fix**: Create new migration to make `user_id` nullable. Update Order model fillable.

### Bug 2: `stock_movements.user_id` FK references wrong table for online orders

| File | Line | Problem |
|---|---|---|
| `app/Modules/Inventory/Services/StockService.php` | 26 | `request()->user()->id` returns Customer ID for online orders, but `stock_movements.user_id` FK references `users` table |

**Fix**: Guard `request()->user()` — store `null` for Customer (online orders), store User ID for staff (POS orders).

---

## Step 2 — Migrate to Sanctum HttpOnly Cookie Auth

**Why**: HttpOnly cookies are XSS-safe vs plain-text tokens in localStorage. Since the first client is web-only (dashboard + storefront), cookies are the right choice.

**Later concern**: Keep `HasApiTokens` trait on User and Customer models — tokens remain available for future mobile apps or third-party integrations (Sanctum checks cookies first, then Bearer token).

### Backend Changes

| Change | File | Detail |
|---|---|---|
| Stateful domains | `.env` | `SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:3000` |
| Session config | `.env` | `SESSION_DRIVER=database`, `SESSION_DOMAIN=localhost` |
| API middleware | `bootstrap/app.php` | Add `EnsureFrontendRequestsAreStateful` to API group |
| Staff login | `AuthController.php` | Replace `createToken()` with `Auth::guard('web')->login()` + `session()->regenerate()` |
| Staff logout | `AuthController.php` | Replace token delete with `Auth::guard('web')->logout()` + `session()->invalidate()` |
| Customer login | `CustomerAuthController.php` | Replace `createToken()` with `Auth::guard('customer')->login()` + `session()->regenerate()` |
| Customer logout | `CustomerAuthController.php` | Replace token delete with `Auth::guard('customer')->logout()` + `session()->invalidate()` |
| Customer register | `CustomerAuthController.php` | Log in after registration |
| Customer profile | `CustomerProfileController.php` | Ensure uses `auth('customer')` guard |

### Frontend Changes

| Change | File | Detail |
|---|---|---|
| Axios config | `lib/axios.ts` | `withCredentials: true`, remove Bearer token interceptor |
| CSRF preflight | `lib/axios.ts` | Call `GET /sanctum/csrf-cookie` before first mutation |
| Auth store | `stores/auth.ts` | Remove token handling; check auth via `GET /auth/me` |
| Login page | `pages/LoginPage.vue` | Call CSRF cookie endpoint before login |

---

## Step 3 — Storefront Public API

### New Endpoints

All scoped by `X-Store` header via `ResolveStore` middleware.

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| `GET` | `/api/storefront/products` | Public | Paginated product listing with category/search filters |
| `GET` | `/api/storefront/products/{slug}` | Public | Single product with variants |
| `GET` | `/api/storefront/categories` | Public | Category list with product counts |
| `GET` | `/api/storefront/settings` | Public | Store config (name, logo, currency, theme from `settings` JSON) |

### Storefront Route Group

```
routes/api.php

Section: Storefront (public + optional customer auth)
  prefix: /api/storefront
  middleware: store (ResolveStore)
  ├── Public (no auth)
  │   ├── GET  /products
  │   ├── GET  /products/{slug}
  │   ├── GET  /categories
  │   └── GET  /settings
  └── Authenticated (auth:customer)
      ├── Customer profile (me, update)
      ├── Addresses CRUD
      ├── Cart CRUD
      ├── Checkout
      └── My orders (list, detail, cancel)
```

---

## Step 4 — Multi-Store Wiring

| Task | Detail |
|---|---|
| Register ResolveStore middleware alias | Add to `bootstrap/app.php` |
| Apply `store` middleware to storefront route group | All storefront routes scoped by `X-Store` |
| Add `store_id` to model `$fillable` | Product, Category, Order, Discount, Supplier, CashSession models |
| Scope storefront queries | `->where('store_id', app('current_store')->id)` on all catalog queries |
| Update `StoreResource` | Expose `settings` for storefront consumption |

---

## Step 5 — Extend Store Model

| New Field | Type | Purpose |
|---|---|---|
| `domain` | `string nullable` | Custom domain for storefront (e.g., `clothing.simpcommerce.com`) |
| `logo` | `string nullable` | Store branding logo path |
| `phone` | `string nullable` | Contact phone |
| `email` | `string nullable` | Contact email |

Create migration + update Store model + update StoreResource.

---

## Step 6 — OAuth (Google) — Deferred

**When**: After Steps 1-5 are complete.

**Approach**: Use `laravel/socialite` package.

| Task | Detail |
|---|---|
| Install | `composer require laravel/socialite` |
| Config | Google OAuth credentials in `.env` (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`) |
| Backend routes | `GET /api/auth/oauth/google` (redirect), `GET /api/auth/oauth/google/callback` (callback) |
| Customer linking | On callback, find or create Customer by Google email, log them in |
| Token/cookie | Issue Sanctum session (cookie) after successful OAuth |
| Frontend | "Sign in with Google" button on login page |
| CSRF note | OAuth callback doesn't use CSRF (it's a browser redirect), so the callback must create a session |

**Details:**
- Google redirect sends user to Google's consent screen
- Google redirects back to callback URL with `code` query param
- Exchange code for access token, fetch user profile from Google
- Match Google email to existing Customer or create new Customer
- Log in as Customer via Sanctum session
- Redirect back to storefront with session cookie set

---

## Step 7 — Plain Product Support (Without Variants) — Deferred

**When**: Needed when a store sells simple products without variant options.

~14 changes required across migrations, models, controllers, services, and cart/order pipelines. See full audit for details.

Key changes:
- Relax `StoreProductRequest` validation (`variants` → optional)
- Add nullable `product_id` to `order_items` and `cart_items` tables
- Add `stock_quantity` to `products` table
- Branch `OrderController`/`OrderService` for product vs variant paths
- Add product-level support to `StockService`

---

## Step 8 — Build Nuxt 3 Storefront(s)

Create separate repos per store:
- `simpcommerce-storefront-clothing`
- `simpcommerce-storefront-electronics`
- etc.

Each storefront:
1. Configures its store slug via `NUXT_PUBLIC_STORE_SLUG=my-store`
2. Sends `X-Store: my-store` header with every API request
3. Uses `$fetch` with credentials for cookie-based auth
4. Calls public endpoints for catalog browsing
5. Calls authenticated endpoints for cart/checkout/orders
