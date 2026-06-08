<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AllowanceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HR-managed allowance types (HRA, conveyance, custom).
 */
class AllowanceTypeController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()?->can('hr.employee.manage'), 403);

        return view('admin.hr.allowance-types-index');
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employee.manage'), 403);

        $rows = AllowanceType::query()->orderBy('sort_order')->orderBy('code')->get();

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employee.manage'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:allowance_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'included_in_esi_gross' => ['sometimes', 'boolean'],
        ]);

        AllowanceType::query()->create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => true,
            'included_in_esi_gross' => $request->boolean('included_in_esi_gross', true),
        ]);

        return response()->json(['status' => true, 'message' => 'Allowance type created.'], 201);
    }

    public function update(Request $request, AllowanceType $allowanceType): JsonResponse
    {
        abort_unless($request->user()?->can('hr.employee.manage'), 403);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
            'included_in_esi_gross' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['is_active'])) {
            $data['is_active'] = $request->boolean('is_active');
        }
        if (isset($data['included_in_esi_gross'])) {
            $data['included_in_esi_gross'] = $request->boolean('included_in_esi_gross');
        }

        $allowanceType->update($data);

        return response()->json(['status' => true, 'message' => 'Allowance type updated.']);
    }
}
