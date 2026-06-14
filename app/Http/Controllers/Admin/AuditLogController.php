<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Read-only audit trail viewer for critical ERP events.
 */
class AuditLogController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $actions = AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.audit-logs.index', [
            'actions' => $actions,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()
            ->select([
                'id',
                'action',
                'subject_type',
                'subject_id',
                'description',
                'user_id',
                'ip_address',
                'created_at',
            ])
            ->with('user:id,name');

        $action = $request->input('action');
        if (is_string($action) && $action !== '') {
            $query->where('action', $action);
        }

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('action', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$term.'%'));
            },
            ['id', 'action', 'created_at'],
        );

        $data = $payload['rows']->map(fn (AuditLog $row) => [
            'action' => $row->action,
            'description' => $row->description !== null && $row->description !== ''
                ? Str::limit($row->description, 80)
                : '—',
            'user' => $row->user?->name ?? 'System',
            'subject' => $row->subject_type !== null
                ? class_basename($row->subject_type).' #'.$row->subject_id
                : '—',
            'ip_address' => $row->ip_address ?? '—',
            'created_at' => $row->created_at?->format('Y-m-d H:i'),
        ])->values()->all();

        return response()->json([
            'draw' => $payload['draw'],
            'recordsTotal' => $payload['recordsTotal'],
            'recordsFiltered' => $payload['recordsFiltered'],
            'data' => $data,
        ]);
    }
}
