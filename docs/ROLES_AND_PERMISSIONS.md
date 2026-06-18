# SimpCommerce — Roles & Permissions Specification

> **Date**: 2026-06-18  
> **System**: Database-driven RBAC via `spatie/laravel-permission`  
> **Guard**: `api`  
> **Roles**: 5 | **Permissions**: 50

---

## 1. Overview

Authorization is handled by `spatie/laravel-permission` (v8). All permission checks are database-driven — roles and permissions are stored in dedicated tables, not hardcoded in PHP enums. The old `users.role` column was dropped in migration `2026_06_18_115201`.

### Architecture

```
Route Middleware
  └── permission:products.create     ← Fast pre-filter (Spatie PermissionMiddleware)
        ↓
FormRequest::authorize()
  └── $user->hasPermissionTo('products.create')  ← Secondary check
        ↓
Controller / Service
  └── $user->hasPermissionTo('products.create')   ← Business logic check
```

### Enforcement Layers

| Layer | Mechanism | Purpose |
|-------|-----------|---------|
| Route | `permission:X` middleware | Fast rejection before controller runs |
| FormRequest | `authorize()` → `hasPermissionTo()` | Validates user can perform the specific action |
| Controller | `StoreScope` trait | Scopes queries to user's store |
| Service | Explicit checks | Business logic guards (e.g., order status transitions) |

---

## 2. Roles

### 2.1 Role Definitions

| Role | Slug | Scope | Description |
|------|------|-------|-------------|
| **Root** | `root` | System-wide | Super administrator. Manages stores, all users, backups, audit logs. Does NOT manage products, orders, inventory, or sales. |
| **Store Owner** | `store_owner` | Per-store | Full store control including user management within their store. Manages products, orders, customers, inventory, discounts, reports, cash sessions, and store settings. |
| **Store Manager** | `store_manager` | Per-store | Same as Owner but cannot manage users or change store settings. |
| **Inventory Staff** | `inventory_staff` | Per-store | Catalog operations: products, variants, stock, suppliers, categories, brands, CSV import/export. Read-only on orders and customers. |
| **Sales Staff** | `sales_staff` | Per-store | POS operations: create orders, customer lookup, cash sessions. Read-only on products. |

### 2.2 Role Assignment

Roles are stored in the `roles` table and assigned to users via the `model_has_roles` pivot table.

```php
// Assign a role
$user->assignRole('store_owner');

// Sync roles (replaces all existing)
$user->syncRoles(['store_manager']);

// Check roles
$user->hasRole('root');
$user->hasAnyRole(['store_owner', 'store_manager']);
$user->hasAllRoles(['store_owner']);
```

---

## 3. Permissions

### 3.1 Complete Permission List (50)

All permissions use guard `api`. Naming convention: `{resource}.{action}`.

#### System (7)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| View stores | `stores.view` | root |
| Create store | `stores.create` | root |
| Update store | `stores.update` | root |
| Delete store | `stores.delete` | root |
| View audit logs | `audit.view` | root |
| Create backups | `backups.create` | root |
| Download backups | `backups.download` | root |

#### User Management (5)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| View all users | `users.view` | root |
| Create any user | `users.create` | root |
| Update any user | `users.update` | root |
| Delete any user | `users.delete` | root |
| Manage store users | `users.manage-store` | root, store_owner |

#### Catalog (11)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| View products | `products.view` | all store roles + root |
| Create product | `products.create` | root, store_owner, store_manager, inventory_staff |
| Update product | `products.update` | root, store_owner, store_manager, inventory_staff |
| Delete product | `products.delete` | root, store_owner, store_manager, inventory_staff |
| Import products | `products.import` | root, store_owner, store_manager, inventory_staff |
| Update variant stock | `variants.update-stock` | root, store_owner, store_manager, inventory_staff |
| View categories | `categories.view` | root, store_owner, store_manager, inventory_staff |
| Create category | `categories.create` | root, store_owner, store_manager, inventory_staff |
| Update category | `categories.update` | root, store_owner, store_manager, inventory_staff |
| Delete category | `categories.delete` | root, store_owner, store_manager, inventory_staff |
| View brands | `brands.view` | root, store_owner, store_manager, inventory_staff |
| Create brand | `brands.create` | root, store_owner, store_manager, inventory_staff |
| Update brand | `brands.update` | root, store_owner, store_manager, inventory_staff |
| Delete brand | `brands.delete` | root, store_owner, store_manager, inventory_staff |

#### Supply Chain (4)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| View suppliers | `suppliers.view` | root, store_owner, store_manager, inventory_staff |
| Create supplier | `suppliers.create` | root, store_owner, store_manager, inventory_staff |
| Update supplier | `suppliers.update` | root, store_owner, store_manager, inventory_staff |
| Delete supplier | `suppliers.delete` | root, store_owner, store_manager, inventory_staff |

#### Inventory (1)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| View stock movements | `stock-movements.view` | root, store_owner, store_manager, inventory_staff |

#### Sales (7)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| View orders | `orders.view` | root, store_owner, store_manager, sales_staff |
| Create order | `orders.create` | root, store_owner, store_manager, sales_staff |
| Update order status | `orders.update-status` | root, store_owner, store_manager |
| Process returns | `orders.return` | root, store_owner, store_manager |
| View invoices | `invoices.view` | root, store_owner, store_manager |
| Print invoices | `invoices.print` | root, store_owner, store_manager |
| View discounts | `discounts.view` | root, store_owner, store_manager |
| Create discount | `discounts.create` | root, store_owner, store_manager |
| Update discount | `discounts.update` | root, store_owner, store_manager |
| Delete discount | `discounts.delete` | root, store_owner, store_manager |

#### CRM (4)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| View customers | `customers.view` | root, store_owner, store_manager, sales_staff |
| Create customer | `customers.create` | root, store_owner, store_manager, sales_staff |
| Update customer | `customers.update` | root, store_owner, store_manager |
| Delete customer | `customers.delete` | root, store_owner, store_manager |

#### POS & Cash (4)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| Access POS | `pos.access` | root, store_owner, store_manager, sales_staff |
| Open cash session | `cash-sessions.open` | root, store_owner, store_manager, sales_staff |
| Close cash session | `cash-sessions.close` | root, store_owner, store_manager, sales_staff |
| View cash sessions | `cash-sessions.view` | root, store_owner, store_manager, sales_staff |

#### Reports (1)
| Permission | Slug | Role Access |
|-----------|------|-------------|
| View reports | `reports.view` | root, store_owner, store_manager |

---

## 4. Role → Permission Matrix

| Group | Permission | root | store_owner | store_manager | inventory_staff | sales_staff |
|-------|-----------|------|-------------|---------------|-----------------|-------------|
| **System** | `stores.*` | ✅ | ❌ | ❌ | ❌ | ❌ |
| | `audit.view` | ✅ | ❌ | ❌ | ❌ | ❌ |
| | `backups.*` | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Users** | `users.*` | ✅ | ❌ | ❌ | ❌ | ❌ |
| | `users.manage-store` | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Catalog** | `products.view` | ✅ | ✅ | ✅ | ✅ | ✅ |
| | `products.create` | ✅ | ✅ | ✅ | ✅ | ❌ |
| | `products.update` | ✅ | ✅ | ✅ | ✅ | ❌ |
| | `products.delete` | ✅ | ✅ | ✅ | ✅ | ❌ |
| | `products.import` | ✅ | ✅ | ✅ | ✅ | ❌ |
| | `variants.update-stock` | ✅ | ✅ | ✅ | ✅ | ❌ |
| | `categories.*` | ✅ | ✅ | ✅ | ✅ | ❌ |
| | `brands.*` | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Suppliers** | `suppliers.*` | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Inventory** | `stock-movements.view` | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Sales** | `orders.view` | ✅ | ✅ | ✅ | ❌ | ✅ |
| | `orders.create` | ✅ | ✅ | ✅ | ❌ | ✅ |
| | `orders.update-status` | ✅ | ✅ | ✅ | ❌ | ❌ |
| | `orders.return` | ✅ | ✅ | ✅ | ❌ | ❌ |
| | `invoices.view` | ✅ | ✅ | ✅ | ❌ | ❌ |
| | `invoices.print` | ✅ | ✅ | ✅ | ❌ | ❌ |
| | `discounts.*` | ✅ | ✅ | ✅ | ❌ | ❌ |
| **CRM** | `customers.view` | ✅ | ✅ | ✅ | ❌ | ✅ |
| | `customers.create` | ✅ | ✅ | ✅ | ❌ | ✅ |
| | `customers.update` | ✅ | ✅ | ✅ | ❌ | ❌ |
| | `customers.delete` | ✅ | ✅ | ✅ | ❌ | ❌ |
| **POS** | `pos.access` | ✅ | ✅ | ✅ | ❌ | ✅ |
| | `cash-sessions.*` | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Reports** | `reports.view` | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## 5. Route Middleware Permissions

Each route uses Spatie's `PermissionMiddleware` via the `permission:` alias:

```php
Route::post('/products', [ProductController::class, 'store'])
    ->middleware('permission:products.create');
```

### Route-to-Permission Mapping

| Route | Method | Permission |
|-------|--------|-----------|
| `/stores` | GET | `stores.view` |
| `/stores` | POST | `stores.create` |
| `/stores/{id}` | PUT | `stores.update` |
| `/stores/{id}` | DELETE | `stores.delete` |
| `/audit-logs` | GET | `audit.view` |
| `/backups` | POST | `backups.create` |
| `/backups/{filename}/download` | GET | `backups.download` |
| `/users` | * | `users.manage-store` |
| `/products` | POST | `products.create` |
| `/products/{id}` | PUT | `products.update` |
| `/products/{id}` | DELETE | `products.delete` |
| `/products/import/csv` | POST | `products.import` |
| `/variants/{id}/stock` | PATCH | `variants.update-stock` |
| `/categories` | POST | `categories.create` |
| `/categories/{id}` | PUT | `categories.update` |
| `/categories/{id}/image` | POST | `categories.update` |
| `/categories/{id}` | DELETE | `categories.delete` |
| `/brands` | POST | `brands.create` |
| `/brands/{id}` | PUT | `brands.update` |
| `/brands/{id}/logo` | POST | `brands.update` |
| `/brands/{id}` | DELETE | `brands.delete` |
| `/orders/{id}/status` | PATCH | `orders.update-status` |
| `/orders/{id}/return` | POST | `orders.return` |
| `/customers/{id}` | PUT | `customers.update` |
| `/customers/{id}` | DELETE | `customers.delete` |
| `/discounts` | POST | `discounts.create` |
| `/discounts/{id}` | PUT | `discounts.update` |
| `/discounts/{id}` | DELETE | `discounts.delete` |
| `/suppliers` | POST | `suppliers.create` |
| `/suppliers/{id}` | PUT | `suppliers.update` |
| `/suppliers/{id}` | DELETE | `suppliers.delete` |
| `/stock-movements` | GET | `stock-movements.view` |
| `/cash-sessions` | POST open | `cash-sessions.open` |
| `/cash-sessions` | POST close | `cash-sessions.close` |
| `/cash-sessions` | GET | `cash-sessions.view` |

---

## 6. Store Isolation

Store scoping is enforced via the `StoreScope` trait using Spatie role checks:

```php
if ($user->hasAnyRole(['store_owner', 'store_manager', 'inventory_staff', 'sales_staff']) && $user->store_id) {
    return $user->store_id;
}
if ($user->isRoot() && request()->header('X-Store')) {
    // Root uses store selector → X-Store header
    return Store::where('slug', request()->header('X-Store'))->first()?->id;
}
```

All permission checks are automatically scoped to the user's store via `X-Store` header and `store_id` FK.

---

## 7. How to Add a New Permission

1. Add to `RolePermissionSeeder::run()`:
```php
Permission::findOrCreate('my-new-permission', 'api');
$storeOwner->givePermissionTo('my-new-permission');
$storeManager->givePermissionTo('my-new-permission');
```

2. Add route middleware:
```php
Route::post('/my-endpoint', [MyController::class, 'store'])
    ->middleware('permission:my-new-permission');
```

3. Run the seeder:
```bash
php artisan db:seed --class=RolePermissionSeeder
```

4. Assign to existing users if needed:
```bash
php artisan tinker --execute 'User::role("store_manager")->each(fn($u) => $u->givePermissionTo("my-new-permission"));'
```

---

## 8. How to Add a New Role

1. Add to `RolePermissionSeeder::run()`:
```php
$viewer = Role::findOrCreate('data_viewer', 'api');
$viewer->syncPermissions([
    'products.view',
    'orders.view',
    'customers.view',
]);
```

2. No route changes needed — routes use permissions, not roles. Any route with `permission:products.view` automatically works for `data_viewer`.

---

## 9. Useful Commands

```bash
# List all roles and their permissions
php artisan permission:show

# Create a permission
php artisan permission:create-permission "my-permission" "api"

# Create a role with permissions
php artisan permission:create-role "my-role" "api" "permission1" "permission2"
```

---

## 10. Seed Data

Roles and permissions are seeded in `database/seeders/RolePermissionSeeder.php`. Called by `DatabaseSeeder`:

```php
$this->seedMainStore();
$this->call(RolePermissionSeeder::class);  // Must run BEFORE creating users
$this->seedUsers();
$this->seedClothingStore();
```

The factory states (`root()`, `salesStaff()`, etc.) use `afterCreating` callbacks to assign Spatie roles:

```php
public function root(): static
{
    return $this->afterCreating(fn (User $user) => $user->assignRole('root'));
}
```
