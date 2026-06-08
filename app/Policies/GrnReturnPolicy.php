<?php

namespace App\Policies;

use App\Models\GrnReturn;
use App\Models\User;

class GrnReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase.grn.create');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase.grn.create') && $user->can('inventory.grn');
    }
}
