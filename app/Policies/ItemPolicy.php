<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

/**
 * SKU / item master.
 */
class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->can('inventory.adjust');
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->can('inventory.adjust');
    }

    public function restore(User $user, Item $item): bool
    {
        return $user->can('inventory.adjust');
    }

    public function forceDelete(User $user, Item $item): bool
    {
        return false;
    }
}
