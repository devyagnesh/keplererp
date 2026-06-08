<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceEntryRequest;
use App\Models\AttendanceEntry;
use App\Models\Employee;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttendanceEntry::class);

        $workDate = $request->query('work_date', now()->toDateString());

        return view('admin.hr.attendance-index', [
            'workDate' => $workDate,
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'emp_code', 'name']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AttendanceEntry::class);

        $workDate = $request->input('work_date', now()->toDateString());

        $query = AttendanceEntry::query()
            ->select(['attendance_entries.id', 'employee_id', 'work_date', 'status', 'created_at'])
            ->whereDate('work_date', $workDate)
            ->with(['employee:id,emp_code,name']);

        $totalForDay = AttendanceEntry::query()->whereDate('work_date', $workDate);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->whereHas('employee', function ($eq) use ($term): void {
                    $eq->where('name', 'like', '%'.$term.'%')
                        ->orWhere('emp_code', 'like', '%'.$term.'%');
                });
            },
            ['id', 'work_date', 'status', 'created_at'],
            'id',
            'desc',
            $totalForDay,
        );

        $data = $payload['rows']->map(function (AttendanceEntry $row) {
            return [
                'employee' => $row->employee?->name ?? '—',
                'emp_code' => $row->employee?->emp_code ?? '—',
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

    public function store(StoreAttendanceEntryRequest $request): JsonResponse
    {
        try {
            $v = $request->validated();
            AttendanceEntry::query()->updateOrCreate(
                [
                    'employee_id' => $v['employee_id'],
                    'work_date' => $v['work_date'],
                ],
                ['status' => $v['status']]
            );

            return response()->json([
                'status' => true,
                'message' => 'Attendance saved successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('AttendanceController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not save attendance.',
            ], 500);
        }
    }
}
