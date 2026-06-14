<?php

namespace App\Modules\Audit\Policies;

use App\Modules\Identity\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool { return $user->isRoot(); }
}
