<?php

namespace App\Modules\Core\Enums;

/**
 * CRUD actions tracked in the audit log for data-change traceability.
 *
 * Each log entry records who did what, to which entity, and the before/after snapshot.
 */
enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
