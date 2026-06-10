<?php

namespace App\Http\Controllers\Employee\Concerns;

use App\Models\Employee;
use Illuminate\Http\Request;

/**
 * Resolves the active Employee record linked to the authenticated user.
 */
trait ResolvesLinkedEmployee
{
    protected function resolveEmployee(Request $request): Employee
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $employee = Employee::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($employee === null) {
            abort(403, 'No employee profile is linked to your account.');
        }

        return $employee;
    }
}
