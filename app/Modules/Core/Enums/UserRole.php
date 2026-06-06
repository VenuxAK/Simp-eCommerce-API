<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible User role values.
 */
enum UserRole: string
{
    case Root = 'root';
    case StoreAdmin = 'store_admin';
    case Staff = 'staff';
}
