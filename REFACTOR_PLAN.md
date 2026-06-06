# SimpCommerce Refactoring Plan

> **Generated**: 2026-06-06
> **Based on**: Full codebase analysis of 14 modules, 24 controllers, 17 models, 8 services, 25 FormRequests, 20 test files (147 tests)

---

## Table of Contents

1. [Phase 1 — Critical Fixes (Security + Bugs)](#phase-1--critical-fixes-security--bugs)
2. [Phase 2 — Architecture Cleanup (No API Changes)](#phase-2--architecture-cleanup-no-api-changes)
3. [Phase 3 — Service Layer Refactoring](#phase-3--service-layer-refactoring)
4. [Phase 4 — Code Quality & Completeness](#phase-4--code-quality--completeness)

---

## Phase 1 — Critical Fixes (Security + Bugs)

**Goal**: Fix all security vulnerabilities and bugs. Self-contained, no API changes, no test breakage.

**Estimated effort**: ~2 hours

| ID | Issue | File(s) | Fix |
|---|---|---|---|
| **C1** | DB password exposed in `ps aux` | `BackupController::dumpMysql()` | Use `--defaults-extra-file` or environment variable instead of CLI `-p` flag |
| **C2** | 15 of 25 FormRequests return `authorize() => true` | `StoreProductRequest`, `StoreCategoryRequest`, `StoreCustomerRequest`, `StoreOrderRequest`, `ReturnOrderRequest`, `StoreAddressRequest`, `StoreStoreRequest` and their Update pairs | Add `$this->user()?->isRoot() || $this->user()?->isStoreAdmin()` or return `false` as appropriate per role |
| **C3** | No database transactions on checkout/cancel | `CheckoutController::placeOrder()`, `MyOrderController::cancel()` | Wrap in `DB::transaction()` |
| **C4** | Staff user seeded with `null store_id` | `DatabaseSeeder::seedUsers()` | Create `main` store before seeding staff user (or use `clothing` store) |
| **C5** | `auth.php` defaults to `web` guard instead of Sanctum | `config/auth.php` | Change `defaults.guard` to `sanctum` |

### Testing Strategy

- C1: Write test for `BackupController::create()` that verifies no password in CLI args
- C2: Write tests verifying unauthorized users get 403 on admin-only endpoints
- C3: Write test verifying rollback on checkout/cancel failure
- C4: Verify seeder produces valid data
- C5: Verify token-based auth still works with changed default guard

---

## Phase 2 — Architecture Cleanup (No API Changes)

**Goal**: Fix structural gaps (relationships, enums, DI) without changing any API behavior.

**Estimated effort**: ~4 hours

### 2a — Missing Relationships

| ID | Issue | Files | Fix |
|---|---|---|---|
| **H4a** | Missing `store(): BelongsTo` on 6 models | `Order`, `Product`, `Category`, `Discount`, `Supplier`, `CashSession` | Add `store()` BelongsTo + `store_id` cast |
| **H4b** | Missing 8 inverse HasMany on Store | `Store` | Add `users()`, `orders()`, `customers()`, `categories()`, `suppliers()`, `cashSessions()`, `products()`, `discounts()` |
| **L3** | Missing inverse relationships across models | `Product`, `ProductVariant`, `Customer`, `Category`, `Address` | Add `cartItems()`, `wishlistItems()`, `discounts()`, `shipments()` inverses |

### 2b — Enum Adoption

| ID | Issue | Current State | Fix |
|---|---|---|---|
| **H6a** | `User.role` as raw string | `'root'`, `'store_admin'`, `'staff'` | Create `UserRole` enum, add `$casts` to User, replace `isRoot()`/`isStoreAdmin()`/`isStaff()` with enum methods |
| **H6b** | `Order.source` as raw string | `'pos'`, `'online'` | Create `OrderSource` enum, add cast to Order |
| **H6c** | `Shipment.method` as raw string | `'cod'`, `'standard'`, `'express'` | Create `ShipmentMethod` enum, add cast to Shipment |
| **H6d** | `Discount.type` / `Discount.applies_to` as raw strings | `'percentage'`/`'fixed'`, `'product'`/`'category'`/`'order'` | Create `DiscountType` and `DiscountScope` enums, add casts |
| **H6e** | `StockMovement.reason` as raw string | `'sale'`, `'purchase'`, `'adjustment'`, `'return'` | Create `StockMovementReason` enum, add cast |
| **H6f** | `Address.type` as raw string | `'shipping'`, `'billing'` | Create `AddressType` enum, add cast |
| **H6g** | `AuditLog.action` as raw string | `'created'`, `'updated'`, `'deleted'` | Create `AuditAction` enum, add cast |
| **H6h** | Magic strings in controllers | 15+ occurrences of `'completed'`, `'processing'`, `'cancelled'`, `'pos'`, `'online'`, `'cash'`, `'adjustment'`, `'main'` | Replace all with Enum references |

### 2c — Dependency Injection

| ID | Issue | Files | Fix |
|---|---|---|---|
| **H7** | `InvoiceNumberGenerator` resolved via `app()` (service locator) | `OrderService:41`, `OnlineOrderService:35` | Use constructor injection with `private readonly InvoiceNumberGenerator $numberGenerator` |

### 2d — CRUD Base Class

| ID | Issue | Files | Fix |
|---|---|---|---|
| **M3** | 5+ controllers with identical CRUD boilerplate | `CategoryController`, `DiscountController`, `SupplierController`, `StoreController`, `UserController` | Create `AbstractCrudController` with `index()`, `store()`, `show()`, `update()`, `destroy()` template methods. Subclasses override: `$modelClass`, `$resourceClass`, `$collectionClass`, `$storeRequestClass`, `$updateRequestClass` |

### 2e — Missing Type Hints

| ID | Issue | Files | Fix |
|---|---|---|---|
| **M7** | Missing `Request $request` parameter | `ReportController`, `StorefrontController`, `AuditLogController`, `StockMovementController` | Add `Request $request` parameter and use it instead of `request()` helper |

### Testing Strategy

- Run existing 147 tests after each sub-phase to ensure no regressions
- Enum creation: verify JSON serialization matches old string values
- Relationships: verify eager-loading paths in existing tests

---

## Phase 3 — Service Layer Refactoring

**Goal**: Move business logic from controllers into dedicated services. Eliminate service duplication.

**Estimated effort**: ~6 hours

### 3a — Order Service Consolidation

| ID | Issue | Current State | Fix |
|---|---|---|---|
| **H1** | ~70% code duplication between `OrderService` and `OnlineOrderService` | Stock deduction, invoice creation, order item creation duplicated across both | Extract shared logic into a trait `HandlesOrderCreation` or a composed class `OrderItemProcessor`. Both services call the shared code with different pre/post hooks (POS adds payment, Online adds shipment + clears cart) |

### 3b — Controller Logic Extraction

| ID | Target Service | Controller + Method | Lines to Move |
|---|---|---|---|
| **H2a** | `DashboardService` | `DashboardController::summary()` | 53 lines — order queries, variant counts, stock aggregation, N+1 fix |
| **H2b** | `ReportService` | `ReportController::sales()`, `bestSellers()`, `paymentMethods()` | ~80 lines — date parsing, SQL aggregation, grouping, mapping |
| **H2c** | `CashSessionService` | `CashSessionController::close()` | 32 lines — order aggregation, balance calculation, settlement |
| **H2d** | `BackupService` | `BackupController::create()`, `dumpPgsql()`, `dumpMysql()`, `dumpSqlite()` | 60 lines — driver-aware dump logic, process execution |
| **H2e** | `OrderService` (extend) | `OrderController::store()`, `returnItems()`, `updateStatus()` | ~180 lines — POS checkout, returns, status state machine |
| **H2f** | `ProductService` (new) | `ProductController::store()`, `update()`, `destroy()` | ~80 lines — variant create/update/delete sync, CSV import orchestration |
| **H2g** | `OAuthService` (new) | `OAuthController::callback()` | 49 lines — customer lookup/creation, store resolution |

### 3c — Store Resolution Consolidation

| ID | Issue | Files | Fix |
|---|---|---|---|
| **H3** | Store resolution duplicated in 5 locations | `StoreScope`, `CustomerController`, `ProductImportService`, `OAuthController`, `CheckoutController` | Make `StoreScope::resolveStoreId()` the single canonical resolution path. Cache result on the container (`app('resolved_store_id')`) to avoid repeated queries. All other locations call `$this->resolveStoreId()` or inject `StoreScope` |

### Testing Strategy

- Write new service unit tests for each extracted service
- Controllers become thin (2-5 lines per method) so existing controller tests implicitly verify the services
- Run full test suite after each extraction

---

## Phase 4 — Code Quality & Completeness

**Goal**: Eliminate code duplication, enforce consistency, fill gaps.

**Estimated effort**: ~5 hours

### 4a — Authorization (Policies)

| ID | Issue | Current State | Fix |
|---|---|---|---|
| **H5** | No Laravel Policies anywhere | Ad-hoc manual checks in controllers | Generate `php artisan make:policy` for: `ProductPolicy`, `CategoryPolicy`, `OrderPolicy`, `DiscountPolicy`, `SupplierPolicy`, `StorePolicy`, `CustomerPolicy`, `UserPolicy`. Register in `AuthServiceProvider`. Controller methods use `$this->authorize()` |

### 4b — Deduplication

| ID | Issue | Locations | Fix |
|---|---|---|---|
| **M1** | Password update pattern duplicated 3x | `UserController:50-54`, `ProfileController:26-30`, `CustomerProfileController:26-30` | Extract `HandlesPasswordUpdates` trait with `handlePasswordUpdate(array &$data, Request $request)` method, or handle in FormRequest via `passedValidation()` hook |
| **M2** | `authorizeOwner` check duplicated 3x | `AddressController:92-97`, `CartController:98-103`, `MyOrderController:36-37,47-48` | Extract `AuthorizesOwnership` trait with `authorizeOwnership(Model $model, string $foreignKey)` |
| **M5** | `when(true, fn($q) => ...)` no-op pattern | `ProductController:37`, `CategoryController:26`, `DiscountController:24`, `CustomerController:31` | Replace with direct `$this->scopeByStore($query)` call |

### 4c — Validation Consistency

| ID | Issue | Files | Fix |
|---|---|---|---|
| **M4** | Inline validation mixed with FormRequests | `CartController::add/update`, `WishlistController::toggle`, `OAuthController::callback`, `CashSessionController::open/close`, `AuthController::login`, `CustomerAuthController::login`, `ProductVariantController::uploadImage` | Create dedicated FormRequests for each inline validator. `CustomerAuthController` uses both patterns — standardize. Use array syntax everywhere instead of pipe syntax |

### 4d — N+1 Elimination

| ID | Issue | Files | Fix |
|---|---|---|---|
| **M6a** | `DashboardController` queries ProductVariant 3x | `DashboardController::summary()` | Single query with conditional aggregation, or move to `DashboardService` with a raw SQL query |
| **M6b** | `ReportController::bestSellers()` eager-loads after aggregation | `ReportController::bestSellers()` | Pre-load relations on the join query, or re-structure the query to avoid the post-hoc load |

### 4e — Hardcoded Data

| ID | Issue | Files | Fix |
|---|---|---|---|
| **M8** | Hardcoded shop metadata | `InvoiceController::print()`, `receipt()`, `pdf()` | Read store name/logo/phone from resolved `app('current_store')` or `Store::settings` |

### 4f — Model Completeness

| ID | Issue | Files | Fix |
|---|---|---|---|
| **L1** | Missing `HasFactory` trait | `CartItem`, `WishlistItem` | Add `HasFactory` + create factory classes |
| **L2** | Missing `SoftDeletes` | `Product`, `Category` | Add `SoftDeletes` trait + migration for `deleted_at` column (CAUTION: changes API behavior — discuss first) |
| **L4** | Missing convenience methods | `Shipment`, `CashSession`, `Discount`, `Address` | Add `isShipped()`, `isDelivered()`, `isOpen()`, `isClosed()`, `scopeActive()`, `isValid()`, `isExpired()`, `scopeDefault()` |
| **L5** | Empty `AppServiceProvider` | `AppServiceProvider` | Add `Model::shouldBeStrict()` in `boot()` for non-production, register service bindings for Phase 3 services |
| **L6** | Missing `quantity` integer cast | `OrderItem` | Add `'quantity' => 'integer'` to `$casts` |
| **L7** | Raw JSON responses instead of API Resources | `StorefrontController::categories()`, `StorefrontController::settings()` | Use `CategoryResource` collection and `StoreResource` |

### 4g — Consistency Fixes

| ID | Issue | Fix |
|---|---|---|
| **M9** | Inconsistent `perPage` (20 vs 50) | Standardize to 20 via `AbstractCrudController` default, allow override |
| **—** | `bcrypt()` vs `Hash::make()` | Standardize on `Hash` facade |
| **—** | `UpdateCategoryRequest` unique rule using raw ID | Use `$this->route('category')->id` or `ignore()` with model binding |
| **—** | `UpdateProductRequest` variant SKU uniqueness scoped by `product_id` instead of variant ID | Fix to ignore by variant ID |
| **—** | `unique` rules not scoped by `store_id` | Add `->where('store_id', resolveStoreId())` to unique rules in multi-tenant tables |

---

## Execution Order

1. **Phase 1** — Deploy immediately (security fixes)
2. **Phase 2** — Next sprint (no API risk, pure internal cleanup)
3. **Phase 3** — Following sprint (largest effort, most test writing)
4. **Phase 4** — Final sprint (polish + gaps)

Phases are designed to be independently mergeable. Each phase should pass the full 147-test suite before merging.

---

## Success Metrics

| Metric | Before | After Phase 4 |
|---|---|---|
| Business logic in controllers | ~500+ lines across 10 controllers | <50 lines (all thin, delegate to services) |
| Magic string usage | 15+ in controllers, 7 in models | 0 (all backed by Enums) |
| Duplicated service code | 150 lines (OrderService/OnlineOrderService) | 0 (shared via trait/composition) |
| Duplicated controller code | 3 authorization patterns, 3 password patterns, 5 CRUD templates | 0 (shared via traits/base class) |
| Missing store relationships | 6 models + 8 Store inverses | 0 |
| FormRequests returning `true` | 15/25 | 0 (all have proper auth checks) |
| `app()` service locator usage | 2 services | 0 |
| N+1 query risks | 2 controllers | 0 |
| Inline validation | 9 methods | 0 (all in FormRequests) |
