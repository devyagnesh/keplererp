<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesLinkedEmployee;
use App\Http\Requests\Employee\EmployeeAttendanceCheckRequest;
use App\Models\AttendanceEntry;
use App\Services\Employee\EmployeeAttendanceCheckService;
use App\Support\ErpDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

/**
 * Employee attendance: GPS check-in/out and personal history.
 */
class EmployeeAttendanceController extends Controller
{
    use ResolvesLinkedEmployee;

    public function __construct(
        protected EmployeeAttendanceCheckService $attendanceCheck
    ) {}

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
        $statusFilter = $request->query('status', '');

        $countsQuery = AttendanceEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start, $end]);

        if (is_string($statusFilter) && $statusFilter !== '') {
            $countsQuery->where('status', $statusFilter);
        }

        $counts = (clone $countsQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('employee.attendance.index', [
            'employee' => $employee,
            'selectedMonth' => $month->format('Y-m'),
            'selectedStatus' => $statusFilter,
            'monthLabel' => $month->format('F Y'),
            'presentDays' => (int) ($counts['present'] ?? 0),
            'absentDays' => (int) ($counts['absent'] ?? 0),
            'halfDays' => (int) ($counts['half'] ?? 0),
            'leaveDays' => (int) ($counts['leave'] ?? 0),
            'todayStatus' => $this->attendanceCheck->todayStatus($employee),
            'gpsMaxAccuracyM' => (float) config('attendance.max_accuracy_m', 150),
            'gpsWarnAccuracyM' => (float) config('attendance.warn_accuracy_m', 50),
        ]);
    }

    public function todayStatus(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);

        return response()->json([
            'status' => true,
            'message' => 'Today status loaded.',
            'data' => $this->attendanceCheck->todayStatus($employee),
        ]);
    }

    public function checkIn(EmployeeAttendanceCheckRequest $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        $this->authorize('selfMark', AttendanceEntry::class);

        try {
            $entry = $this->attendanceCheck->checkIn(
                $employee,
                $request->user(),
                $request->geolocationPayload()
            );

            return response()->json([
                'status' => true,
                'message' => 'Checked in successfully at '.now()->format('H:i'),
                'data' => $this->attendanceCheck->todayStatus($employee),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('EmployeeAttendanceController@checkIn failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not record check-in.',
            ], 500);
        }
    }

    public function checkOut(EmployeeAttendanceCheckRequest $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        $this->authorize('selfMark', AttendanceEntry::class);

        try {
            $this->attendanceCheck->checkOut(
                $employee,
                $request->user(),
                $request->geolocationPayload()
            );

            return response()->json([
                'status' => true,
                'message' => 'Checked out successfully at '.now()->format('H:i'),
                'data' => $this->attendanceCheck->todayStatus($employee),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('EmployeeAttendanceController@checkOut failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not record check-out.',
            ], 500);
        }
    }

    public function data(Request $request): JsonResponse
    {
        try {
            $employee = $this->resolveEmployee($request);
            $this->authorize('viewAny', AttendanceEntry::class);

            $monthInput = $request->input('month', now()->format('Y-m'));
            $month = is_string($monthInput) && preg_match('/^\d{4}-\d{2}$/', $monthInput)
                ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
                : now()->startOfMonth();

            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();
            $status = $request->input('status');

            $selectColumns = [
                'attendance_entries.id',
                'work_date',
                'status',
                'created_at',
            ];

            if (Schema::hasColumn('attendance_entries', 'check_in_at')) {
                $selectColumns = array_merge($selectColumns, [
                    'check_in_at',
                    'check_out_at',
                    'check_in_latitude',
                    'check_in_longitude',
                    'check_out_latitude',
                    'check_out_longitude',
                    'check_in_accuracy_m',
                    'check_out_accuracy_m',
                    'check_in_address',
                    'check_out_address',
                    'source',
                ]);
            }

            $query = AttendanceEntry::query()
                ->select($selectColumns)
                ->where('employee_id', $employee->id)
                ->whereBetween('work_date', [$start, $end]);

            $baseCount = AttendanceEntry::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('work_date', [$start, $end]);

            if (is_string($status) && $status !== '') {
                $query->where('status', $status);
                $baseCount->where('status', $status);
            }

            $orderableColumns = ['work_date', 'status', 'created_at'];
            if (Schema::hasColumn('attendance_entries', 'check_in_at')) {
                $orderableColumns[] = 'check_in_at';
            }

            $payload = ErpDataTable::run(
                $query,
                $request,
                function ($q, string $term): void {
                    $q->where('status', 'like', '%'.$term.'%');
                },
                $orderableColumns,
                'work_date',
                'desc',
                $baseCount,
            );

            $data = $payload['rows']->map(function (AttendanceEntry $row) {
                return [
                    'work_date' => $row->work_date?->format('Y-m-d'),
                    'status' => $row->status,
                    'check_in' => $row->check_in_at?->format('H:i:s') ?? '—',
                    'check_out' => $row->check_out_at?->format('H:i:s') ?? '—',
                    'check_in_location' => $row->checkInLocationLabel(),
                    'check_out_location' => $row->checkOutLocationLabel(),
                    'source' => $row->source ?? '—',
                    'created_at' => $row->created_at?->format('Y-m-d H:i'),
                ];
            })->values()->all();

            return response()->json([
                'draw' => $payload['draw'],
                'recordsTotal' => $payload['recordsTotal'],
                'recordsFiltered' => $payload['recordsFiltered'],
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            Log::error('EmployeeAttendanceController@data failed', ['message' => $e->getMessage()]);

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Could not load attendance records.',
            ], 500);
        }
    }
}
