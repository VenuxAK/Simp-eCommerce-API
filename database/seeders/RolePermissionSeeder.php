<?php

namespace Database\Seeders;

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
            'stores.view', 'stores.create', 'stores.update', 'stores.delete',
            'audit.view',
            'backups.create', 'backups.download',
            'users.view', 'users.create', 'users.update', 'users.delete',
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
        ];

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

        $root->syncPermissions(Permission::all());

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

        $inventoryStaff->syncPermissions([
            'products.view', 'products.create', 'products.update', 'products.delete',
            'products.import',
            'variants.update-stock',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'brands.view', 'brands.create', 'brands.update', 'brands.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
            'stock-movements.view',
        ]);

        $salesStaff->syncPermissions([
            'products.view',
            'orders.create', 'orders.view',
            'customers.view', 'customers.create',
            'pos.access',
            'cash-sessions.open', 'cash-sessions.close', 'cash-sessions.view',
        ]);
    }
}
