<?php

namespace App\Modules\Sales\Policies;

use App\Modules\Identity\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool { return $user->isStoreUser(); }
    public function view(User $user): bool { return $user->isStoreUser(); }
    public function print(User $user): bool { return $user->isStoreUser(); }
    public function pdf(User $user): bool { return $user->isStoreUser(); }
    public function receipt(User $user): bool { return $user->isStoreUser(); }
}
