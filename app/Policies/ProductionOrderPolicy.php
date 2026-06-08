<?php

namespace App\Policies;

use App\Models\ProductionOrder;
use App\Models\User;

class ProductionOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('production.order.create') || $user->can('production.log');
    }

    public function view(User $user, ProductionOrder $productionOrder): bool
    {
        return $user->can('production.order.create') || $user->can('production.log');
    }

    public function create(User $user): bool
    {
        return $user->can('production.order.create');
    }

    public function update(User $user, ProductionOrder $productionOrder): bool
    {
        return $user->can('production.log') || $user->can('production.order.create');
    }

    public function delete(User $user, ProductionOrder $productionOrder): bool
    {
        return $user->can('production.order.create') && $productionOrder->status === 'planned';
    }

    public function restore(User $user, ProductionOrder $productionOrder): bool
    {
        return false;
    }

    public function forceDelete(User $user, ProductionOrder $productionOrder): bool
    {
        return false;
    }
}
