<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesLinkedEmployee;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only employee profile for self-service portal.
 */
class EmployeeProfileController extends Controller
{
    use ResolvesLinkedEmployee;

    public function show(Request $request): View
    {
        $employee = $this->resolveEmployee($request);
        $employee->loadMissing(['employeeAllowances.allowanceType:id,name']);

        return view('employee.profile.show', [
            'employee' => $employee,
        ]);
    }
}
