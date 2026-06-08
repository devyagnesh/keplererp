<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hr.payroll.run');
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('hr.payroll.run');
    }

    public function create(User $user): bool
    {
        return $user->can('hr.payroll.run');
    }

    public function update(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('hr.payroll.run') && $payrollRun->status === 'draft';
    }

    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('hr.payroll.run') && $payrollRun->status === 'draft';
    }

    public function restore(User $user, PayrollRun $payrollRun): bool
    {
        return false;
    }

    public function forceDelete(User $user, PayrollRun $payrollRun): bool
    {
        return false;
    }
}
