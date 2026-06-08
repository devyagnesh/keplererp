<?php

namespace App\Policies;

use App\Models\InventoryBalance;
use App\Models\User;

/**
 * Read-only stock position listing.
 */
class InventoryBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, InventoryBalance $inventoryBalance): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, InventoryBalance $inventoryBalance): bool
    {
        return false;
    }

    public function delete(User $user, InventoryBalance $inventoryBalance): bool
    {
        return false;
    }

    public function restore(User $user, InventoryBalance $inventoryBalance): bool
    {
        return false;
    }

    public function forceDelete(User $user, InventoryBalance $inventoryBalance): bool
    {
        return false;
    }
}
