<?php

namespace Tests\Feature;

use App\Models\AllowanceType;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\PayrollRunService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ProcessesPayrollRuns;
use Tests\TestCase;

class PayrollRulesTest extends TestCase
{
    use ProcessesPayrollRuns;
    use RefreshDatabase;

    public function test_payroll_uses_configured_pf_rate(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $settings = PayrollSetting::current();
        $settings->update([
            'pf_enabled' => true,
            'pf_employee_rate' => 0.10,
            'pf_employer_rate' => 0.10,
            'pf_wage_ceiling' => 15000,
            'pf_max_monthly_contribution' => 0,
            'pf_allow_opt_in_above_ceiling' => false,
        ]);

        $employee = Employee::query()->create([
            'emp_code' => 'PF10',
            'name' => 'PF Test',
            'is_active' => true,
            'basic_salary' => '10000.00',
            'pf_number' => 'PFTEST01',
            'pf_opted_in' => true,
        ]);

        $calc = app(PayrollRunService::class)->calculateForEmployee(
            $employee,
            now()->startOfMonth(),
            now()->endOfMonth(),
            (int) now()->daysInMonth,
            $settings
        );

        $this->assertSame('1000.00', $calc['pf_deduction']);
        $this->assertSame('1000.00', $calc['pf_employer']);
    }

    public function test_allowances_increase_gross_on_payroll_run(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $hraType = AllowanceType::query()->where('code', 'HRA')->first();
        $this->assertNotNull($hraType);

        $employee = Employee::query()->create([
            'emp_code' => 'ALW1',
            'name' => 'Allowance Test',
            'is_active' => true,
            'basic_salary' => '10000.00',
            'pf_number' => 'PFALW1',
            'pf_opted_in' => true,
        ]);

        EmployeeAllowance::query()->create([
            'employee_id' => $employee->id,
            'allowance_type_id' => $hraType->id,
            'monthly_amount' => '2500.00',
        ]);

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $run = PayrollRun::query()->create([
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->lockAndProcessPayroll($run, $user);

        $detail = PayrollDetail::query()->where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame('12500.00', bcadd((string) $detail->gross_salary, '0', 2));
        $this->assertSame('2500.00', bcadd((string) $detail->hra, '0', 2));

        $breakdown = $detail->earnings_breakdown;
        $this->assertIsArray($breakdown);
        $this->assertCount(1, $breakdown['allowances'] ?? []);
    }

    public function test_hr_can_update_payroll_settings(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->putJson(route('admin.hr.payroll-settings.update'), [
                'pf_enabled' => '1',
                'pf_employee_rate' => '0.11',
                'pf_employer_rate' => '0.11',
                'pf_wage_ceiling' => '15000',
                'pf_max_monthly_contribution' => '1800',
                'pf_allow_opt_in_above_ceiling' => '1',
                'esi_enabled' => '1',
                'esi_gross_ceiling' => '21000',
                'esi_employee_rate' => '0.0075',
                'esi_employer_rate' => '0.0325',
                'pt_enabled' => '1',
                'pt_monthly_amount' => '200',
                'pt_min_gross' => '10000',
            ])
            ->assertOk();

        $settings = PayrollSetting::current()->fresh();
        $this->assertEqualsWithDelta(0.11, (float) $settings->pf_employee_rate, 0.0001);
    }
}
