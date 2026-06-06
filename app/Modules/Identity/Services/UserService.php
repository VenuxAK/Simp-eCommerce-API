<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Sales\Models\Order;

/**
 * Encapsulates user lifecycle logic — creation, update, deletion with safety guards.
 *
 * Keeps password hashing in one place so controllers don't need to remember it.
 * canDelete() enforces business rules before removal to prevent accidental
 * deletion of root users or users with historical orders.
 */
class UserService
{
    public function createUser(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        return User::create($data);
    }

    public function updateUser(User $user, array $data): void
    {
        // Only rehash when a non-empty password is provided; ignore or unset empty values
        // so the existing password hash isn't accidentally overwritten with an empty string.
        if (isset($data['password'])) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = bcrypt($data['password']);
            }
        }

        $user->update($data);
    }

    /**
     * Validate that the target user can be deleted before performing the deletion.
     *
     * Protects against self-deletion, deletion of root users by other roots,
     * and deletion of users with linked orders (data integrity). Returns an
     * error message string or null if deletion is safe.
     */
    public function canDelete(User $currentUser, User $targetUser): ?string
    {
        if ($targetUser->id === $currentUser->id) {
            return 'Cannot delete yourself.';
        }

        if ($targetUser->isRoot() && $currentUser->isRoot()) {
            return 'Cannot delete another root user.';
        }

        $orderCount = Order::where('user_id', $targetUser->id)->count();

        if ($orderCount > 0) {
            return "Cannot delete user: {$orderCount} order(s) are linked to this user.";
        }

        return null;
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}
