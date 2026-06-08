<?php

namespace App\Policies;

use App\Models\AttendanceEntry;
use App\Models\User;

class AttendanceEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hr.attendance.mark');
    }

    public function view(User $user, AttendanceEntry $attendanceEntry): bool
    {
        return $user->can('hr.attendance.mark');
    }

    public function create(User $user): bool
    {
        return $user->can('hr.attendance.mark');
    }

    public function update(User $user, AttendanceEntry $attendanceEntry): bool
    {
        return $user->can('hr.attendance.mark');
    }

    public function delete(User $user, AttendanceEntry $attendanceEntry): bool
    {
        return $user->can('hr.attendance.mark');
    }

    public function restore(User $user, AttendanceEntry $attendanceEntry): bool
    {
        return false;
    }

    public function forceDelete(User $user, AttendanceEntry $attendanceEntry): bool
    {
        return false;
    }
}
