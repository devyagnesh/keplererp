<?php

namespace App\Enums;

/**
 * Supplier lifecycle for purchase master (approval gate).
 */
enum VendorStatus: string
{
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Blocked = 'blocked';

    /**
     * Human-readable label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending approval',
            self::Active => 'Active',
            self::Blocked => 'Blocked',
        };
    }
}
