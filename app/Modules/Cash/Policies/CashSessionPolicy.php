<?php

namespace App\Modules\Cash\Policies;

use App\Modules\Identity\Models\User;

class CashSessionPolicy
{
    public function viewAny(User $user): bool { return $user->isStoreUser(); }
    public function open(User $user): bool { return $user->isStoreUser(); }
    public function close(User $user): bool { return $user->isStoreUser(); }
}
