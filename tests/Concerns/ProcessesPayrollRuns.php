<?php

namespace Tests\Concerns;

use App\Models\PayrollRun;
use App\Models\User;

/**
 * Lock attendance, process, and approve payroll runs in feature tests.
 */
trait ProcessesPayrollRuns
{
    protected function lockAndProcessPayroll(PayrollRun $run, User $user): PayrollRun
    {
        $this->actingAs($user)->postJson(route('admin.hr.payroll-runs.lock-attendance', $run))->assertOk();
        $this->actingAs($user)->postJson(route('admin.hr.payroll-runs.process', $run))->assertOk();

        return $run->fresh();
    }

    protected function approvePayroll(PayrollRun $run, User $user): PayrollRun
    {
        $this->actingAs($user)->postJson(route('admin.hr.payroll-runs.approve', $run))->assertOk();

        return $run->fresh();
    }
}
