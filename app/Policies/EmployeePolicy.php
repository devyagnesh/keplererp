<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hr.employee.manage');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('hr.employee.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('hr.employee.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('hr.employee.manage');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('hr.employee.manage');
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->can('hr.employee.manage');
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return false;
    }
}
