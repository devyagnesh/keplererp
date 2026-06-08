<?php

namespace App\Policies;

use App\Models\PurchaseRequisition;
use App\Models\User;

class PurchaseRequisitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase.pr.create');
    }

    public function view(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase.pr.create');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase.pr.create');
    }

    public function update(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase.pr.create');
    }

    public function delete(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase.pr.create') && $purchaseRequisition->status === 'draft';
    }

    public function submit(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase.pr.create') && $purchaseRequisition->status === 'draft';
    }

    public function approve(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase.po.approve') && $purchaseRequisition->status === 'pending_approval';
    }

    public function reject(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase.po.approve') && $purchaseRequisition->status === 'pending_approval';
    }

    public function convert(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase.po.create') && $purchaseRequisition->status === 'approved';
    }

    public function restore(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return false;
    }

    public function forceDelete(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return false;
    }
}
