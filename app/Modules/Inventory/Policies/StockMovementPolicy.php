<?php

namespace App\Modules\Inventory\Policies;

use App\Modules\Identity\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool { return $user->canManageCatalog(); }
}
