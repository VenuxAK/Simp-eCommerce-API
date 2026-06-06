<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible AuditLog action values.
 */
enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
