<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesLinkedEmployee;
use App\Http\Requests\Employee\EmployeeStoreLeaveApplicationRequest;
use App\Models\LeaveApplication;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Self-service leave applications for the logged-in employee.
 */
class EmployeeLeaveController extends Controller
{
    use ResolvesLinkedEmployee;

    public function index(Request $request): View
    {
        $employee = $this->resolveEmployee($request);
        $this->authorize('create', LeaveApplication::class);

        return view('employee.leave.index', [
            'employee' => $employee,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        $this->authorize('create', LeaveApplication::class);

        $query = LeaveApplication::query()
            ->select(['leave_applications.id', 'start_date', 'end_date', 'leave_type', 'status', 'rejected_reason', 'created_at'])
            ->where('employee_id', $employee->id);

        $baseCount = LeaveApplication::query()->where('employee_id', $employee->id);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('leave_type', 'like', '%'.$term.'%')
                    ->orWhere('status', 'like', '%'.$term.'%');
            },
            ['start_date', 'status', 'created_at'],
            'id',
            'desc',
            $baseCount,
        );

        $data = $payload['rows']->map(function (LeaveApplication $row) {
            return [
                'period' => $row->start_date?->format('Y-m-d').' → '.$row->end_date?->format('Y-m-d'),
                'leave_type' => $row->leave_type,
                'status' => $row->status,
                'reason_note' => $row->status === 'rejected' ? ($row->rejected_reason ?? '—') : '—',
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

    public function store(EmployeeStoreLeaveApplicationRequest $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request);
        $this->authorize('create', LeaveApplication::class);

        try {
            $validated = $request->validated();
            LeaveApplication::query()->create([
                'employee_id' => $employee->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'leave_type' => $validated['leave_type'],
                'reason' => $validated['reason'] ?? null,
                'status' => 'pending',
                'created_by' => $request->user()?->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Leave application submitted successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('EmployeeLeaveController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not submit leave application.',
            ], 500);
        }
    }
}
