<?php

namespace App\Modules\Core\Enums;

/**
 * Staff role hierarchy for the multi-store platform.
 *
 * Roles:
 * - Root:          System administrator (cross-store) — manages stores, users, backups, audit.
 *                  Does NOT manage products, orders, inventory, or sales.
 * - StoreOwner:    Full per-store control including user management within their store.
 * - StoreManager:  Per-store operations (products, orders, customers, inventory, reports).
 *                  Cannot manage users or store settings.
 * - InventoryStaff: Per-store catalog management — products, variants, stock, suppliers.
 *                  Read-only on orders and customers.
 * - SalesStaff:    Per-store sales operations — POS orders, customer lookup, cash sessions.
 *                  Read-only on products and inventory.
 */
enum UserRole: string
{
    case Root = 'root';
    case StoreOwner = 'store_owner';
    case StoreManager = 'store_manager';
    case InventoryStaff = 'inventory_staff';
    case SalesStaff = 'sales_staff';
}
