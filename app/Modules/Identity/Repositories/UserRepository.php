<?php

namespace App\Modules\Identity\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Identity\Models\User;

/**
 * Internal staff-user data access layer.
 *
 * Provides authentication lookups and aggregate helpers
 * on top of the base CRUD inherited from Repository.
 */
class UserRepository extends Repository
{
    protected function model(): string
    {
        return User::class;
    }

    /**
     * Retrieve a user by their email address.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Return the total number of orders associated with the given user.
     */
    public function findWithOrdersCount(int $id): int
    {
        return User::withCount('orders')->find($id)?->orders_count ?? 0;
    }
}
