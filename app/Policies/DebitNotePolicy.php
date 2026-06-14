<?php

namespace App\Policies;

use App\Models\DebitNote;
use App\Models\User;

class DebitNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase.grn.create');
    }

    public function view(User $user, DebitNote $debitNote): bool
    {
        return $user->can('purchase.grn.create');
    }
}
