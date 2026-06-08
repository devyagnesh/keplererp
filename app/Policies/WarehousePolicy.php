<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

/**
 * Warehouse master — aligned with inventory permissions.
 */
class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.adjust');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.adjust');
    }

    public function restore(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.adjust');
    }

    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        return false;
    }
}
