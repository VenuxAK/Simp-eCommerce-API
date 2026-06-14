<?php

namespace App\Modules\Supplier\Policies;

use App\Modules\Identity\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool { return $user->isStoreUser(); }
    public function view(User $user): bool { return $user->isStoreUser(); }

    public function create(User $user): bool { return $user->canManageSuppliers(); }
    public function update(User $user): bool { return $user->canManageSuppliers(); }
    public function delete(User $user): bool { return $user->canManageSuppliers(); }
}
