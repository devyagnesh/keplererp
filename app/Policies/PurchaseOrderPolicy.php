<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase.po.create') || $user->can('purchase.po.approve');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase.po.create') || $user->can('purchase.po.approve');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase.po.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase.po.create') && $purchaseOrder->status === 'draft';
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase.po.create') && $purchaseOrder->status === 'draft';
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase.po.approve') && $purchaseOrder->status === 'draft';
    }

    public function financeApprove(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('finance.payment.approve') && $purchaseOrder->status === 'pending_finance';
    }

    /**
     * Draft PO may be rejected by same role that approves (four-eyes enforced in controller).
     */
    public function reject(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase.po.approve') && $purchaseOrder->status === 'draft';
    }

    /**
     * After final approval, mark PO as sent to vendor (SRS procurement step 6).
     */
    public function markSent(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase.po.approve') && $purchaseOrder->status === 'approved';
    }

    public function restore(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }

    public function forceDelete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }
}
