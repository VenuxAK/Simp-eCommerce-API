<?php

namespace Database\Seeders;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'api';

        // ─── Permissions ───────────────────────────────────────

        $permissions = [
            // System
            'stores.view', 'stores.create', 'stores.update', 'stores.delete',
            'audit.view',
            'backups.create', 'backups.download',

            // Users
            'users.view', 'users.create', 'users.update', 'users.delete',
            'users.manage-store',

            // Catalog
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.import',
            'variants.update-stock',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',

            // Supply chain
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',

            // Inventory
            'stock-movements.view',

            // Sales
            'orders.view', 'orders.create', 'orders.update-status', 'orders.return',
            'invoices.view', 'invoices.print',
            'discounts.view', 'discounts.create', 'discounts.update', 'discounts.delete',

            // CRM
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',

            // POS
            'pos.access',
            'cash-sessions.open', 'cash-sessions.close', 'cash-sessions.view',

            // Reports
            'reports.view',
        ];

        // Create permissions (skip if already exists — idempotent).
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        // ─── Roles ─────────────────────────────────────────────

        $root = Role::findOrCreate('root', $guard);
        $storeOwner = Role::findOrCreate('store_owner', $guard);
        $storeManager = Role::findOrCreate('store_manager', $guard);
        $inventoryStaff = Role::findOrCreate('inventory_staff', $guard);
        $salesStaff = Role::findOrCreate('sales_staff', $guard);

        // ─── Assign Permissions ────────────────────────────────

        // Root — all permissions.
        $root->syncPermissions(Permission::all());

        // Store Owner — all store-scoped permissions.
        $storeOwner->syncPermissions([
            'users.manage-store',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.import',
            'variants.update-stock',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
            'stock-movements.view',
            'orders.view', 'orders.create', 'orders.update-status', 'orders.return',
            'invoices.view', 'invoices.print',
            'discounts.view', 'discounts.create', 'discounts.update', 'discounts.delete',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'pos.access',
            'cash-sessions.open', 'cash-sessions.close', 'cash-sessions.view',
            'reports.view',
        ]);

        // Store Manager — same as owner minus user management.
        $storeManager->syncPermissions([
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.import',
            'variants.update-stock',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
            'stock-movements.view',
            'orders.view', 'orders.create', 'orders.update-status', 'orders.return',
            'invoices.view', 'invoices.print',
            'discounts.view', 'discounts.create', 'discounts.update', 'discounts.delete',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'pos.access',
            'cash-sessions.open', 'cash-sessions.close', 'cash-sessions.view',
            'reports.view',
        ]);

        // Inventory Staff — catalog + suppliers.
        $inventoryStaff->syncPermissions([
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.import',
            'variants.update-stock',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
            'stock-movements.view',
        ]);

        // Sales Staff — POS + customer lookup.
        $salesStaff->syncPermissions([
            'products.view',
            'orders.create', 'orders.view',
            'customers.view', 'customers.create',
            'pos.access',
            'cash-sessions.open', 'cash-sessions.close', 'cash-sessions.view',
        ]);

        // ─── Assign Roles to Existing Users ────────────────────

        foreach (User::all() as $user) {
            $spatieRole = match ($user->role?->value) {
                'root' => 'root',
                'store_owner' => 'store_owner',
                'store_manager' => 'store_manager',
                'inventory_staff' => 'inventory_staff',
                'sales_staff' => 'sales_staff',
                default => 'sales_staff',
            };
            $user->assignRole($spatieRole);
        }
    }
}
