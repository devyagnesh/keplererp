<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.payment.approve') || $user->can('finance.reports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('finance.payment.approve');
    }
}
