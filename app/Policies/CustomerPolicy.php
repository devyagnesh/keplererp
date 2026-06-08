<?php

namespace App\Policies;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\User;

/**
 * Authorization for customer master records.
 */
class CustomerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }

    /**
     * Block an active customer (stop new sales until reactivated).
     */
    public function block(User $user, Customer $customer): bool
    {
        return $user->can('customers.edit') && $customer->status === CustomerStatus::Active;
    }

    /**
     * Reactivate a blocked customer.
     */
    public function activate(User $user, Customer $customer): bool
    {
        return $user->can('customers.edit') && $customer->status === CustomerStatus::Blocked;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Customer $customer): bool
    {
        return $user->can('customers.edit');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        return false;
    }
}
