<?php

namespace App\Policies;

use App\Models\BillOfMaterial;
use App\Models\User;

class BillOfMaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('production.bom.create');
    }

    public function view(User $user, BillOfMaterial $billOfMaterial): bool
    {
        return $user->can('production.bom.create');
    }

    public function create(User $user): bool
    {
        return $user->can('production.bom.create');
    }

    public function update(User $user, BillOfMaterial $billOfMaterial): bool
    {
        return $user->can('production.bom.create');
    }

    public function delete(User $user, BillOfMaterial $billOfMaterial): bool
    {
        return $user->can('production.bom.create');
    }

    public function restore(User $user, BillOfMaterial $billOfMaterial): bool
    {
        return false;
    }

    public function forceDelete(User $user, BillOfMaterial $billOfMaterial): bool
    {
        return false;
    }
}
