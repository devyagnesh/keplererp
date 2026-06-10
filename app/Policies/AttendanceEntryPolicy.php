<?php

namespace App\Policies;

use App\Models\AttendanceEntry;
use App\Models\User;

class AttendanceEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hr.attendance.mark') || $user->can('hr.attendance.view');
    }

    public function view(User $user, AttendanceEntry $attendanceEntry): bool
    {
        if ($user->can('hr.attendance.mark')) {
            return true;
        }

        if (! $user->can('hr.attendance.view')) {
            return false;
        }

        return $attendanceEntry->employee?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('hr.attendance.mark');
    }

    public function selfMark(User $user): bool
    {
        return $user->can('hr.attendance.self_mark');
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
