<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesLinkedEmployee;
use App\Models\AttendanceEntry;
use App\Support\ErpDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only attendance history for the logged-in employee.
 */
class EmployeeAttendanceController extends Controller
{
    use ResolvesLinkedEmployee;

    public function index(Request $request): View
    {
        $employee = $this->resolveEmployee($request);
        $this->authorize('viewAny', AttendanceEntry::class);

        $monthInput = $request->query('month', now()->format('Y-m'));
        $month = is_string($monthInput) && preg_match('/^\d{4}-\d{2}$/', $monthInput)
            ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
            : now()->startOfMonth();

        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $counts = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start, $end])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('employee.attendance.index', [
            'employee' => $employee,
            'selectedMonth' => $month->format('Y-m'),
            'monthLabel' => $month->format('F Y'),
            'presentDays' => (int) ($counts['present'] ?? 0),
            'absentDays' => (int) ($counts['absent'] ?? 0),
            'halfDays' => (int) ($counts['half'] ?? 0),
            'leaveDays' => (int) ($counts['leave'] ?? 0),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        $this->authorize('viewAny', AttendanceEntry::class);

        $monthInput = $request->input('month', now()->format('Y-m'));
        $month = is_string($monthInput) && preg_match('/^\d{4}-\d{2}$/', $monthInput)
            ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
            : now()->startOfMonth();

        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $query = AttendanceEntry::query()
            ->select(['attendance_entries.id', 'work_date', 'status', 'created_at'])
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start, $end]);

        $baseCount = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start, $end]);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('status', 'like', '%'.$term.'%');
            },
            ['work_date', 'status', 'created_at'],
            'work_date',
            'desc',
            $baseCount,
        );

        $data = $payload['rows']->map(function (AttendanceEntry $row) {
            return [
                'work_date' => $row->work_date?->format('Y-m-d'),
                'status' => $row->status,
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }
}
