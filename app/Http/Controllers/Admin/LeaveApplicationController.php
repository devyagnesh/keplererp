<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveApplicationRequest;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * HR leave applications (SRS Module 10).
 */
class LeaveApplicationController extends Controller
{
    public function __construct(
        protected WhatsAppNotificationService $whatsapp
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', LeaveApplication::class);

        return view('admin.hr.leave-index', [
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'emp_code']),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LeaveApplication::class);

        $query = LeaveApplication::query()
            ->select(['leave_applications.id', 'employee_id', 'start_date', 'end_date', 'leave_type', 'status', 'created_at'])
            ->with(['employee:id,name,emp_code']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('leave_type', 'like', '%'.$term.'%')
                    ->orWhere('status', 'like', '%'.$term.'%');
            },
            ['id', 'start_date', 'status', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (LeaveApplication $row) use ($actor) {
            return [
                'employee' => $row->employee?->name ?? '—',
                'period' => $row->start_date?->format('Y-m-d').' → '.$row->end_date?->format('Y-m-d'),
                'leave_type' => $row->leave_type,
                'status' => $row->status,
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'action' => $this->buildActionHtml($row, $actor),
            ];
        })->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }

    protected function buildActionHtml(LeaveApplication $leave, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('approve', $leave) && $leave->status === 'pending') {
            $html .= '<button type="button" class="btn btn-sm btn-success btn-wave js-leave-approve" data-url="'
                .e(route('admin.hr.leave.approve', $leave)).'">Approve</button>';
            $html .= '<button type="button" class="btn btn-sm btn-outline-danger btn-wave js-leave-reject" data-url="'
                .e(route('admin.hr.leave.reject', $leave)).'">Reject</button>';
        }
        $html .= '</div>';

        return $html;
    }

    public function store(StoreLeaveApplicationRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        try {
            $v = $request->validated();
            LeaveApplication::query()->create([
                'employee_id' => $v['employee_id'],
                'start_date' => $v['start_date'],
                'end_date' => $v['end_date'],
                'leave_type' => $v['leave_type'],
                'reason' => $v['reason'] ?? null,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Leave application submitted.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('LeaveApplicationController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not submit leave application.',
            ], 500);
        }
    }

    public function approve(LeaveApplication $leaveApplication): JsonResponse
    {
        $this->authorize('approve', $leaveApplication);

        if ($leaveApplication->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Only pending leave applications can be approved.',
            ], 422);
        }

        try {
            $user = request()->user();
            $leaveApplication->update([
                'status' => 'approved',
                'approved_by' => $user?->id,
                'approved_at' => now(),
            ]);
            $this->whatsapp->notifyLeaveApproved($leaveApplication, $user?->name ?? 'HR');

            return response()->json([
                'status' => true,
                'message' => 'Leave approved.',
            ]);
        } catch (Throwable $e) {
            Log::error('LeaveApplicationController@approve failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not approve leave.',
            ], 500);
        }
    }

    public function reject(Request $request, LeaveApplication $leaveApplication): JsonResponse
    {
        $this->authorize('approve', $leaveApplication);

        if ($leaveApplication->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Only pending leave applications can be rejected.',
            ], 422);
        }

        $reason = (string) $request->input('rejected_reason', '');
        if ($reason === '') {
            return response()->json([
                'status' => false,
                'message' => 'Rejection reason is required.',
            ], 422);
        }

        $leaveApplication->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Leave rejected.',
        ]);
    }
}
