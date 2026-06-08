<?php

namespace App\Policies;

use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;

/**
 * Authorization for supplier (vendor) master records.
 */
class VendorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vendors.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('vendors.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.delete');
    }

    /**
     * Move pending vendor to active (purchase use).
     */
    public function approve(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.approve') && $vendor->status === VendorStatus::PendingApproval;
    }

    /**
     * Block an active vendor.
     */
    public function block(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.edit') && $vendor->status === VendorStatus::Active;
    }

    /**
     * Unblock a blocked vendor back to active.
     */
    public function activate(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.edit') && $vendor->status === VendorStatus::Blocked;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.edit');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vendor $vendor): bool
    {
        return false;
    }
}
