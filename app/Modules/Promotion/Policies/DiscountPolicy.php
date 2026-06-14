<?php

namespace App\Modules\Promotion\Policies;

use App\Modules\Identity\Models\User;

class DiscountPolicy
{
    public function viewAny(User $user): bool { return $user->isStoreUser(); }
    public function view(User $user): bool { return $user->isStoreUser(); }

    public function create(User $user): bool { return $user->canManageSales(); }
    public function update(User $user): bool { return $user->canManageSales(); }
    public function delete(User $user): bool { return $user->canManageSales(); }
}
