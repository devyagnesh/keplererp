<?php

namespace App\Policies;

use App\Models\StockReconciliation;
use App\Models\User;

class StockReconciliationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    public function view(User $user, StockReconciliation $reconciliation): bool
    {
        return $user->can('inventory.adjust');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    public function update(User $user, StockReconciliation $reconciliation): bool
    {
        return $user->can('inventory.adjust')
            && $reconciliation->status === StockReconciliation::STATUS_DRAFT;
    }

    public function post(User $user, StockReconciliation $reconciliation): bool
    {
        return $user->can('inventory.adjust')
            && $reconciliation->status === StockReconciliation::STATUS_DRAFT;
    }
}
