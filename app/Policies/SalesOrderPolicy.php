<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.order.create') || $user->can('sales.dispatch');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales.order.create') || $user->can('sales.dispatch');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.order.create');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales.order.create') && $salesOrder->status !== 'dispatched';
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales.order.create') && $salesOrder->status === 'draft';
    }

    public function process(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales.dispatch')
            && $salesOrder->status === 'confirmed'
            && $salesOrder->dispatched_at === null;
    }

    public function dispatch(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales.dispatch')
            && in_array($salesOrder->status, ['confirmed', 'processing'], true)
            && $salesOrder->dispatched_at === null;
    }

    public function restore(User $user, SalesOrder $salesOrder): bool
    {
        return false;
    }

    public function forceDelete(User $user, SalesOrder $salesOrder): bool
    {
        return false;
    }
}
