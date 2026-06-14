<?php

namespace App\Modules\System\Policies;

use App\Modules\Identity\Models\User;

class BackupPolicy
{
    public function viewAny(User $user): bool { return $user->isRoot(); }
    public function create(User $user): bool { return $user->isRoot(); }
    public function download(User $user): bool { return $user->isRoot(); }
}
