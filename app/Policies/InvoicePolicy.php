<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.invoice.create');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('sales.invoice.create');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.invoice.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
