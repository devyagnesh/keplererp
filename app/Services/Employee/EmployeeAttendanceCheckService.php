<?php

namespace App\Services\Employee;

use App\Models\AttendanceEntry;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Self-service GPS check-in and check-out for linked employees.
 */
class EmployeeAttendanceCheckService
{
    /**
     * @param  array<string, mixed>  $geo
     */
    public function checkIn(Employee $employee, User $user, array $geo): AttendanceEntry
    {
        $today = now()->toDateString();
        $entry = AttendanceEntry::query()->firstOrNew([
            'employee_id' => $employee->id,
            'work_date' => $today,
        ]);

        if ($entry->exists && $entry->check_in_at !== null) {
            throw new \RuntimeException('You have already checked in today.');
        }

        $entry->fill([
            'status' => 'present',
            'source' => 'self_service',
            'marked_by_user_id' => $user->id,
            'check_in_at' => now(),
            'check_in_latitude' => $geo['latitude'],
            'check_in_longitude' => $geo['longitude'],
            'check_in_accuracy_m' => $geo['accuracy_m'],
            'check_in_altitude_m' => $geo['altitude_m'],
            'check_in_meta' => $geo,
        ]);
        $entry->save();

        return $entry->fresh();
    }

    /**
     * @param  array<string, mixed>  $geo
     */
    public function checkOut(Employee $employee, User $user, array $geo): AttendanceEntry
    {
        $today = now()->toDateString();
        $entry = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($entry === null || $entry->check_in_at === null) {
            throw new \RuntimeException('Check in before checking out.');
        }

        if ($entry->check_out_at !== null) {
            throw new \RuntimeException('You have already checked out today.');
        }

        $entry->update([
            'check_out_at' => now(),
            'check_out_latitude' => $geo['latitude'],
            'check_out_longitude' => $geo['longitude'],
            'check_out_accuracy_m' => $geo['accuracy_m'],
            'check_out_altitude_m' => $geo['altitude_m'],
            'check_out_meta' => $geo,
            'marked_by_user_id' => $user->id,
        ]);

        return $entry->fresh();
    }

    /**
     * Today's attendance row for dashboard actions.
     */
    public function todayEntry(Employee $employee): ?AttendanceEntry
    {
        return AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', now()->toDateString())
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function todayStatus(Employee $employee): array
    {
        $entry = $this->todayEntry($employee);

        return [
            'work_date' => now()->toDateString(),
            'has_checked_in' => $entry?->check_in_at !== null,
            'has_checked_out' => $entry?->check_out_at !== null,
            'check_in_at' => $entry?->check_in_at instanceof Carbon ? $entry->check_in_at->format('H:i:s') : null,
            'check_out_at' => $entry?->check_out_at instanceof Carbon ? $entry->check_out_at->format('H:i:s') : null,
            'check_in_latitude' => $entry?->check_in_latitude,
            'check_in_longitude' => $entry?->check_in_longitude,
            'check_in_accuracy_m' => $entry?->check_in_accuracy_m,
            'check_out_latitude' => $entry?->check_out_latitude,
            'check_out_longitude' => $entry?->check_out_longitude,
            'check_out_accuracy_m' => $entry?->check_out_accuracy_m,
            'status' => $entry?->status,
        ];
    }
}
