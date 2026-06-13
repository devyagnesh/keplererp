<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WarehouseTransfer;

class WarehouseTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.transfer');
    }

    public function view(User $user, WarehouseTransfer $transfer): bool
    {
        return $user->can('inventory.transfer');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.transfer');
    }

    public function approve(User $user, WarehouseTransfer $transfer): bool
    {
        return $user->can('inventory.transfer')
            && $transfer->status === WarehouseTransfer::STATUS_DRAFT;
    }

    public function dispatch(User $user, WarehouseTransfer $transfer): bool
    {
        return $user->can('inventory.transfer')
            && $transfer->status === WarehouseTransfer::STATUS_APPROVED;
    }

    public function receive(User $user, WarehouseTransfer $transfer): bool
    {
        return $user->can('inventory.transfer')
            && $transfer->status === WarehouseTransfer::STATUS_IN_TRANSIT;
    }

    public function cancel(User $user, WarehouseTransfer $transfer): bool
    {
        return $user->can('inventory.transfer')
            && in_array($transfer->status, [WarehouseTransfer::STATUS_DRAFT, WarehouseTransfer::STATUS_APPROVED], true);
    }
}
