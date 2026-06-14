<?php

namespace App\Policies;

use App\Modules\Identity\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageStoreUsers();
    }

    public function view(User $user, User $target): bool
    {
        if ($user->isRoot()) {
            return true;
        }

        return $user->isStoreOwner() && $target->store_id === $user->store_id;
    }

    public function create(User $user): bool
    {
        return $user->canManageStoreUsers();
    }

    public function update(User $user, User $target): bool
    {
        if ($user->isRoot()) {
            return true;
        }

        return $user->isStoreOwner() && $target->store_id === $user->store_id;
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->isRoot()) {
            return !$target->isRoot();
        }

        return false;
    }
}
