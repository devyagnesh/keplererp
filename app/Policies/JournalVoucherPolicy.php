<?php

namespace App\Policies;

use App\Models\JournalVoucher;
use App\Models\User;

class JournalVoucherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.voucher.create') || $user->can('finance.reports.view');
    }

    public function view(User $user, JournalVoucher $journalVoucher): bool
    {
        return $user->can('finance.voucher.create') || $user->can('finance.reports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('finance.voucher.create');
    }

    public function update(User $user, JournalVoucher $journalVoucher): bool
    {
        return $user->can('finance.voucher.create') && $journalVoucher->status === 'draft';
    }

    public function delete(User $user, JournalVoucher $journalVoucher): bool
    {
        return $user->can('finance.voucher.create') && $journalVoucher->status === 'draft';
    }

    public function post(User $user, JournalVoucher $journalVoucher): bool
    {
        return $user->can('finance.payment.approve') && $journalVoucher->status === 'draft';
    }

    public function restore(User $user, JournalVoucher $journalVoucher): bool
    {
        return false;
    }

    public function forceDelete(User $user, JournalVoucher $journalVoucher): bool
    {
        return false;
    }
}
