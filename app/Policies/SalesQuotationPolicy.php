<?php

namespace App\Policies;

use App\Models\SalesQuotation;
use App\Models\User;

class SalesQuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.quotation.create');
    }

    public function view(User $user, SalesQuotation $salesQuotation): bool
    {
        return $user->can('sales.quotation.create');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.quotation.create');
    }

    public function update(User $user, SalesQuotation $salesQuotation): bool
    {
        return $user->can('sales.quotation.create');
    }

    public function delete(User $user, SalesQuotation $salesQuotation): bool
    {
        return $user->can('sales.quotation.create');
    }

    public function convert(User $user, SalesQuotation $salesQuotation): bool
    {
        return $user->can('sales.order.create')
            && in_array($salesQuotation->status, ['draft', 'sent', 'accepted'], true);
    }

    public function send(User $user, SalesQuotation $salesQuotation): bool
    {
        return $user->can('sales.quotation.create')
            && in_array($salesQuotation->status, ['draft'], true);
    }

    public function restore(User $user, SalesQuotation $salesQuotation): bool
    {
        return false;
    }

    public function forceDelete(User $user, SalesQuotation $salesQuotation): bool
    {
        return false;
    }
}
