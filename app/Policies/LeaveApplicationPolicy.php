<?php

namespace App\Policies;

use App\Models\LeaveApplication;
use App\Models\User;

class LeaveApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hr.employee.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('hr.employee.manage') || $user->can('hr.leave.apply');
    }

    public function approve(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->can('hr.employee.manage') && $leaveApplication->status === 'pending';
    }
}
