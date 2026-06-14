<?php

namespace App\Modules\Store\Policies;

use App\Modules\Identity\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool { return $user->isRoot(); }
    public function view(User $user): bool { return $user->isRoot(); }
    public function create(User $user): bool { return $user->isRoot(); }
    public function update(User $user): bool { return $user->isRoot(); }
    public function delete(User $user): bool { return $user->isRoot(); }
}
