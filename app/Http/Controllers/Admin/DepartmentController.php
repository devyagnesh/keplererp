<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()?->can('hr.employee.manage'), 403);

        return view('admin.hr.departments-index');
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employee.manage'), 403);

        $rows = Department::query()->orderBy('code')->get(['id', 'code', 'name', 'is_active']);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employee.manage'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:100'],
        ]);
        Department::query()->create(array_merge($data, ['is_active' => true]));

        return response()->json(['status' => true, 'message' => 'Department created.'], 201);
    }
}
