<?php

namespace App\Modules\Catalog\Policies;

use App\Modules\Identity\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool { return $user->isStoreUser(); }
    public function view(User $user): bool { return $user->isStoreUser(); }

    public function create(User $user): bool { return $user->canManageCatalog(); }
    public function update(User $user): bool { return $user->canManageCatalog(); }
    public function delete(User $user): bool { return $user->canManageCatalog(); }
}
