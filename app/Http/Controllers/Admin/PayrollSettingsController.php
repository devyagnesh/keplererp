<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePayrollSettingsRequest;
use App\Models\PayrollSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * HR configuration for PF, ESI, and professional tax rules.
 */
class PayrollSettingsController extends Controller
{
    public function edit(): View
    {
        abort_unless(request()->user()?->can('hr.employee.manage'), 403);

        return view('admin.hr.payroll-settings', [
            'settings' => PayrollSetting::current(),
        ]);
    }

    public function update(UpdatePayrollSettingsRequest $request): JsonResponse
    {
        $settings = PayrollSetting::current();
        $data = $request->validated();
        $data['pf_enabled'] = $request->boolean('pf_enabled');
        $data['pf_allow_opt_in_above_ceiling'] = $request->boolean('pf_allow_opt_in_above_ceiling');
        $data['esi_enabled'] = $request->boolean('esi_enabled');
        $data['pt_enabled'] = $request->boolean('pt_enabled');
        $settings->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Payroll rules saved successfully.',
        ]);
    }
}
