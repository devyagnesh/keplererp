<?php

namespace App\Enums;

/**
 * Customer account state for sales master.
 */
enum CustomerStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';

    /**
     * Human-readable label for UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Blocked => 'Blocked',
        };
    }
}
