<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\AllowanceType;
use App\Models\Employee;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use App\Support\ErpDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct(
        protected PayrollCalculationService $payrollCalc
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Employee::class);

        return view('admin.hr.employees-index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $query = Employee::query()->select(['id', 'emp_code', 'name', 'department', 'is_active', 'created_at']);

        $payload = ErpDataTable::run(
            $query,
            $request,
            function ($q, string $term): void {
                $q->where('emp_code', 'like', '%'.$term.'%')
                    ->orWhere('name', 'like', '%'.$term.'%')
                    ->orWhere('department', 'like', '%'.$term.'%');
            },
            ['id', 'emp_code', 'name', 'department', 'is_active', 'created_at'],
        );

        $actor = $request->user();
        if ($actor === null) {
            abort(403);
        }

        $data = $payload['rows']->map(function (Employee $row) use ($actor) {
            return [
                'emp_code' => $row->emp_code,
                'name' => $row->name,
                'department' => $row->department ?? '—',
                'is_active' => $row->is_active ? 'Yes' : 'No',
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

    protected function buildActionHtml(Employee $employee, User $actor): string
    {
        $html = '<div class="btn-list d-flex flex-wrap gap-1">';
        if ($actor->can('update', $employee)) {
            $html .= '<a href="'.e(route('admin.hr.employees.edit', $employee)).'" class="btn btn-sm btn-primary btn-wave">Edit</a>';
        }
        if ($actor->can('delete', $employee)) {
            $html .= '<button type="button" class="btn btn-sm btn-danger btn-wave js-employee-delete" data-url="'
                .e(route('admin.hr.employees.destroy', $employee)).'">Delete</button>';
        }
        $html .= '</div>';

        return $html;
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('admin.hr.employees-create', $this->employeeFormData());
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $data = $this->normalizeEmployeeData($request->validated(), $request);
            $allowances = $data['allowances'] ?? [];
            unset($data['allowances']);
            $employee = Employee::query()->create($data);
            $this->payrollCalc->syncEmployeeAllowances($employee, $allowances);

            return response()->json([
                'status' => true,
                'message' => 'Employee created successfully.',
            ], 201);
        } catch (Throwable $e) {
            Log::error('EmployeeController@store failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not create employee.',
            ], 500);
        }
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        $employee->load(['employeeAllowances']);

        return view('admin.hr.employees-edit', array_merge($this->employeeFormData(), [
            'employee' => $employee,
        ]));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        try {
            $data = $this->normalizeEmployeeData($request->validated(), $request);
            $allowances = $data['allowances'] ?? [];
            unset($data['allowances']);
            $employee->update($data);
            $this->payrollCalc->syncEmployeeAllowances($employee, $allowances);

            return response()->json([
                'status' => true,
                'message' => 'Employee updated successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('EmployeeController@update failed', ['message' => $e->getMessage(), 'id' => $employee->id]);

            return response()->json([
                'status' => false,
                'message' => 'Could not update employee.',
            ], 500);
        }
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        try {
            $employee->delete();

            return response()->json([
                'status' => true,
                'message' => 'Employee deleted successfully.',
            ]);
        } catch (Throwable $e) {
            Log::error('EmployeeController@destroy failed', ['message' => $e->getMessage(), 'id' => $employee->id]);

            return response()->json([
                'status' => false,
                'message' => 'Could not delete employee.',
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function employeeFormData(): array
    {
        return [
            'departments' => \App\Models\Department::query()->where('is_active', true)->orderBy('name')->get(),
            'designations' => \App\Models\Designation::query()->where('is_active', true)->orderBy('name')->get(),
            'allowanceTypes' => AllowanceType::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeEmployeeData(array $data, FormRequest $request): array
    {
        $data['is_active'] = $request->boolean('is_active', true);
        $data['pf_opted_in'] = $request->boolean('pf_opted_in', true);
        $data['basic_salary'] = bcadd((string) ($data['basic_salary'] ?? 0), '0', 2);
        $data['allowances'] = $data['allowances'] ?? [];

        return $data;
    }
}
