<?php

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.invoice.create');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.invoice.create');
    }
}
