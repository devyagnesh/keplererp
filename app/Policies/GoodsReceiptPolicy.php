<?php

namespace App\Policies;

use App\Models\GoodsReceipt;
use App\Models\User;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase.grn.create') || $user->can('inventory.grn');
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('purchase.grn.create') || $user->can('inventory.grn');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase.grn.create') && $user->can('inventory.grn');
    }

    public function update(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return false;
    }

    public function delete(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return false;
    }

    public function restore(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return false;
    }

    public function forceDelete(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return false;
    }
}
