<?php

namespace App\Modules\Core\Enums;

/**
 * Internal staff role hierarchy — Root (super-admin), StoreAdmin (per-store manager), Staff (operator).
 *
 * Root bypasses all store scoping; StoreAdmin and Staff are bound to a specific store.
 */
enum UserRole: string
{
    case Root = 'root';
    case StoreAdmin = 'store_admin';
    case Staff = 'staff';
}
