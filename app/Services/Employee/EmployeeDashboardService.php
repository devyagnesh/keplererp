<?php

namespace App\Services\Employee;

use App\Models\AttendanceEntry;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\PayrollDetail;
use Carbon\Carbon;

/**
 * Aggregates self-service dashboard metrics for a linked employee.
 */
class EmployeeDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Employee $employee, ?Carbon $month = null): array
    {
        $month = $month ?? now();
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $attendanceCounts = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start, $end])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingLeaves = LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $lastPayslip = PayrollDetail::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->where('status', 'processed'))
            ->with(['payrollRun:id,period_year,period_month,processed_at'])
            ->orderByDesc('id')
            ->first();

        $recentAttendance = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('work_date')
            ->limit(7)
            ->get(['work_date', 'status']);

        $recentLeaves = LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'start_date', 'end_date', 'leave_type', 'status', 'created_at']);

        return [
            'month_label' => $month->format('F Y'),
            'present_days' => (int) ($attendanceCounts['present'] ?? 0),
            'absent_days' => (int) ($attendanceCounts['absent'] ?? 0),
            'half_days' => (int) ($attendanceCounts['half'] ?? 0),
            'leave_days' => (int) ($attendanceCounts['leave'] ?? 0),
            'pending_leaves' => $pendingLeaves,
            'last_payslip' => $lastPayslip,
            'recent_attendance' => $recentAttendance,
            'recent_leaves' => $recentLeaves,
        ];
    }
}
