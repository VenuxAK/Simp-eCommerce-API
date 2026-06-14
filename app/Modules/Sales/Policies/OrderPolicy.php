<?php

namespace App\Modules\Sales\Policies;

use App\Modules\Identity\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool { return $user->isStoreUser(); }
    public function view(User $user): bool { return $user->isStoreUser(); }
    public function create(User $user): bool { return $user->isStoreUser(); }

    public function updateStatus(User $user): bool { return $user->canManageSales(); }
    public function returnItems(User $user): bool { return $user->canManageSales(); }
}
