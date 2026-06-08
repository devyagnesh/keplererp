<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()?->can('hr.employee.manage'), 403);

        return view('admin.hr.designations-index');
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employee.manage'), 403);

        return response()->json([
            'data' => Designation::query()->orderBy('code')->get(['id', 'code', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employee.manage'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:designations,code'],
            'name' => ['required', 'string', 'max:100'],
        ]);
        Designation::query()->create(array_merge($data, ['is_active' => true]));

        return response()->json(['status' => true, 'message' => 'Designation created.'], 201);
    }
}
