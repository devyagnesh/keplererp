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

/**
 * HR attendance marking, listing, and GPS map oversight.
 */
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
        $employeeId = $request->input('employee_id');
        $status = $request->input('status');
        $source = $request->input('source');

        $query = AttendanceEntry::query()
            ->select([
                'attendance_entries.id',
                'employee_id',
                'work_date',
                'status',
                'source',
                'check_in_at',
                'check_out_at',
                'check_in_latitude',
                'check_in_longitude',
                'check_out_latitude',
                'check_out_longitude',
                'check_in_accuracy_m',
                'check_out_accuracy_m',
                'created_at',
            ])
            ->whereDate('work_date', $workDate)
            ->with(['employee:id,emp_code,name']);

        $totalForDay = AttendanceEntry::query()->whereDate('work_date', $workDate);

        if (is_numeric($employeeId) && (int) $employeeId > 0) {
            $query->where('employee_id', (int) $employeeId);
            $totalForDay->where('employee_id', (int) $employeeId);
        }

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
            $totalForDay->where('status', $status);
        }

        if (is_string($source) && $source !== '') {
            $query->where('source', $source);
            $totalForDay->where('source', $source);
        }

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->whereHas('employee', function ($eq) use ($term): void {
                    $eq->where('name', 'like', '%'.$term.'%')
                        ->orWhere('emp_code', 'like', '%'.$term.'%');
                });
            },
            ['id', 'work_date', 'status', 'check_in_at', 'created_at'],
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
                'source' => $row->source,
                'check_in' => $row->check_in_at?->format('H:i:s') ?? '—',
                'check_out' => $row->check_out_at?->format('H:i:s') ?? '—',
                'check_in_location' => $row->checkInLocationLabel(),
                'check_out_location' => $row->checkOutLocationLabel(),
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

    /**
     * GeoJSON-style marker payload for the HR attendance map.
     */
    public function mapData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AttendanceEntry::class);

        $workDate = $request->input('work_date', now()->toDateString());
        $employeeId = $request->input('employee_id');
        $source = $request->input('source');

        $query = AttendanceEntry::query()
            ->whereDate('work_date', $workDate)
            ->with(['employee:id,emp_code,name']);

        if (is_numeric($employeeId) && (int) $employeeId > 0) {
            $query->where('employee_id', (int) $employeeId);
        }

        if (is_string($source) && $source !== '') {
            $query->where('source', $source);
        }

        $markers = [];
        $latSum = 0.0;
        $lngSum = 0.0;
        $coordCount = 0;

        foreach ($query->get() as $entry) {
            $employeeLabel = ($entry->employee?->emp_code ?? '?').' — '.($entry->employee?->name ?? 'Unknown');

            if ($entry->hasCheckInCoordinates()) {
                $lat = (float) $entry->check_in_latitude;
                $lng = (float) $entry->check_in_longitude;
                $markers[] = [
                    'id' => $entry->id.'-in',
                    'type' => 'check_in',
                    'employee' => $employeeLabel,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'accuracy_m' => $entry->check_in_accuracy_m !== null ? (float) $entry->check_in_accuracy_m : null,
                    'address' => $entry->check_in_address,
                    'map_url' => $entry->checkInMapUrl(),
                    'recorded_at' => $entry->check_in_at?->format('Y-m-d H:i:s'),
                ];
                $latSum += $lat;
                $lngSum += $lng;
                $coordCount++;
            }

            if ($entry->hasCheckOutCoordinates()) {
                $lat = (float) $entry->check_out_latitude;
                $lng = (float) $entry->check_out_longitude;
                $markers[] = [
                    'id' => $entry->id.'-out',
                    'type' => 'check_out',
                    'employee' => $employeeLabel,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'accuracy_m' => $entry->check_out_accuracy_m !== null ? (float) $entry->check_out_accuracy_m : null,
                    'address' => $entry->check_out_address,
                    'map_url' => $entry->checkOutMapUrl(),
                    'recorded_at' => $entry->check_out_at?->format('Y-m-d H:i:s'),
                ];
                $latSum += $lat;
                $lngSum += $lng;
                $coordCount++;
            }
        }

        $center = $coordCount > 0
            ? ['latitude' => $latSum / $coordCount, 'longitude' => $lngSum / $coordCount]
            : ['latitude' => 20.5937, 'longitude' => 78.9629];

        return response()->json([
            'status' => true,
            'message' => 'Map data loaded.',
            'data' => [
                'markers' => $markers,
                'center' => $center,
                'marker_count' => count($markers),
            ],
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
                [
                    'status' => $v['status'],
                    'source' => AttendanceEntry::SOURCE_HR_MANUAL,
                    'marked_by_user_id' => $request->user()?->id,
                ]
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
